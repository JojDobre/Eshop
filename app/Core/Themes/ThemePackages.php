<?php

namespace App\Core\Themes;

use App\Core\Eshop;
use Composer\Semver\Semver;
use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

/**
 * Nahratie šablóny zo ZIP, export do ZIP a odstránenie.
 *
 * Šablóny obsahujú PHP kód (Blade), preto sa nahrávajú iba z dôveryhodných zdrojov.
 * Kontroluje sa aspoň štruktúra balíka, povolené typy súborov a cesty (bez „..“ a odkazov).
 */
class ThemePackages
{
    /** Povolené prípony súborov v šablóne. */
    public const ALLOWED_EXTENSIONS = ['php', 'json', 'css', 'js', 'map', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'otf', 'md', 'txt'];

    public const MAX_FILES = 2000;

    public const MAX_SIZE = 50 * 1024 * 1024;

    public function __construct(private readonly ThemeManager $themes, private readonly string $themesPath) {}

    /**
     * Nainštaluje šablónu zo ZIP a vráti ju.
     *
     * @throws RuntimeException so zrozumiteľnou správou
     */
    public function install(string $zipPath, bool $overwrite = false): Theme
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException(__('admin.themes.errors.not_zip'));
        }

        try {
            [$prefix, $manifest] = $this->inspect($zip);
            $name = (string) ($manifest['name'] ?? '');

            if (! preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
                throw new RuntimeException(__('admin.themes.errors.invalid_name'));
            }

            $required = $manifest['requires']['eshop'] ?? null;

            if (is_string($required) && ! self::compatible($required)) {
                throw new RuntimeException(__('admin.themes.errors.incompatible', ['required' => $required, 'version' => Eshop::VERSION]));
            }

            $target = $this->themesPath.'/'.$name;

            if (is_dir($target)) {
                if (! $overwrite) {
                    throw new RuntimeException(__('admin.themes.errors.exists', ['name' => $name]));
                }

                if ($name === config('eshop.default_theme')) {
                    throw new RuntimeException(__('admin.themes.errors.default_protected'));
                }
            }

            // Rozbalenie do dočasného priečinka, až potom výmena (pri chybe ostane pôvodná šablóna).
            $temp = $this->themesPath.'/.upload-'.bin2hex(random_bytes(4));
            File::ensureDirectoryExists($temp);

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = (string) $zip->getNameIndex($i);

                if (str_ends_with($entry, '/') || ($prefix !== '' && ! str_starts_with($entry, $prefix))) {
                    continue;
                }

                $relative = substr($entry, strlen($prefix));
                $destination = $temp.'/'.$relative;
                File::ensureDirectoryExists(dirname($destination));
                $stream = $zip->getStream($entry);

                if ($stream === false) {
                    throw new RuntimeException(__('admin.themes.errors.corrupt'));
                }

                file_put_contents($destination, $stream);
                fclose($stream);
            }

            if (is_dir($target)) {
                File::deleteDirectory($target);
            }

            rename($temp, $target);
        } finally {
            $zip->close();

            if (isset($temp) && is_dir($temp)) {
                File::deleteDirectory($temp);
            }
        }

        $this->themes->flush();

        return $this->themes->find($name) ?? throw new RuntimeException(__('admin.themes.errors.invalid_manifest'));
    }

    /**
     * Vytvorí ZIP so šablónou (napr. na zálohu alebo prenos do iného obchodu).
     */
    public function export(Theme $theme): string
    {
        $path = storage_path('app/private/theme-'.$theme->name.'-'.$theme->version.'.zip');
        File::ensureDirectoryExists(dirname($path));
        @unlink($path);

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (File::allFiles($theme->path) as $file) {
            $zip->addFile($file->getPathname(), $theme->name.'/'.str_replace('\\', '/', $file->getRelativePathname()));
        }

        $zip->close();

        return $path;
    }

    /**
     * Odstráni šablónu (nie aktívnu, predvolenú ani rodiča inej šablóny).
     */
    public function delete(Theme $theme): void
    {
        if ($reason = $this->cannotDelete($theme)) {
            throw new RuntimeException($reason);
        }

        File::deleteDirectory($theme->path);
        $this->themes->flush();
    }

    /** Dôvod, prečo šablónu nemožno odstrániť (null = možno). */
    public function cannotDelete(Theme $theme): ?string
    {
        return match (true) {
            $theme->name === config('eshop.default_theme') => __('admin.themes.errors.default_protected'),
            $theme->name === $this->themes->activeName() => __('admin.themes.errors.active'),
            collect($this->themes->all())->contains(fn (Theme $other) => $other->parent === $theme->name) => __('admin.themes.errors.is_parent'),
            default => null,
        };
    }

    /** Funguje šablóna s touto verziou jadra? */
    public static function compatible(?string $constraint): bool
    {
        if ($constraint === null || $constraint === '') {
            return true;
        }

        try {
            return Semver::satisfies(Eshop::VERSION, $constraint);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Skontroluje obsah ZIP a vráti predponu priečinka a theme.json.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function inspect(ZipArchive $zip): array
    {
        if ($zip->numFiles === 0 || $zip->numFiles > self::MAX_FILES) {
            throw new RuntimeException(__('admin.themes.errors.too_many_files'));
        }

        $prefix = null;
        $size = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entry = (string) ($stat['name'] ?? '');
            $size += (int) ($stat['size'] ?? 0);

            if ($entry === '' || str_contains($entry, '..') || str_starts_with($entry, '/') || str_contains($entry, '\\') || str_contains($entry, "\0")) {
                throw new RuntimeException(__('admin.themes.errors.unsafe_path', ['path' => $entry]));
            }

            // Symbolické odkazy v ZIP (Unix atribúty) sú zakázané.
            if ($zip->getExternalAttributesIndex($i, $opsys, $attributes) && $opsys === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000) {
                throw new RuntimeException(__('admin.themes.errors.unsafe_path', ['path' => $entry]));
            }

            if (str_ends_with($entry, '/')) {
                continue;
            }

            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));

            if (! in_array($extension, self::ALLOWED_EXTENSIONS, true) || str_starts_with(basename($entry), '.')) {
                throw new RuntimeException(__('admin.themes.errors.forbidden_file', ['path' => $entry]));
            }

            if (basename($entry) === 'theme.json' && ($prefix === null || strlen(dirname($entry)) < strlen(rtrim($prefix, '/')))) {
                $prefix = dirname($entry) === '.' ? '' : dirname($entry).'/';
            }
        }

        if ($size > self::MAX_SIZE) {
            throw new RuntimeException(__('admin.themes.errors.too_large'));
        }

        if ($prefix === null || substr_count($prefix, '/') > 1) {
            throw new RuntimeException(__('admin.themes.errors.no_manifest'));
        }

        $manifest = json_decode((string) $zip->getFromName($prefix.'theme.json'), true);

        if (! is_array($manifest)) {
            throw new RuntimeException(__('admin.themes.errors.invalid_manifest'));
        }

        return [$prefix, $manifest];
    }
}
