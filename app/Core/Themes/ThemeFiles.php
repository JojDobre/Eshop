<?php

namespace App\Core\Themes;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use ParseError;
use RuntimeException;

/**
 * Úprava súborov šablóny v administrácii. Pred každou zmenou sa uloží záloha, ktorá sa dá obnoviť.
 * Predvolená šablóna sa neupravuje (prepísala by ju aktualizácia) – na úpravy slúži odvodená šablóna.
 */
class ThemeFiles
{
    /** Textové súbory, ktoré sa dajú upravovať. */
    public const EDITABLE = ['php', 'css', 'js', 'json', 'md', 'txt', 'svg'];

    public const MAX_SIZE = 512 * 1024;

    public function __construct(private readonly ThemeManager $themes, private readonly string $backupPath) {}

    public function canEdit(Theme $theme): bool
    {
        return $theme->name !== config('eshop.default_theme');
    }

    /**
     * Upraviteľné súbory šablóny (relatívne cesty).
     *
     * @return list<string>
     */
    public function files(Theme $theme): array
    {
        $files = [];

        foreach (File::allFiles($theme->path) as $file) {
            if (in_array(strtolower($file->getExtension()), self::EDITABLE, true) && $file->getSize() <= self::MAX_SIZE) {
                $files[] = str_replace('\\', '/', $file->getRelativePathname());
            }
        }

        sort($files);

        return $files;
    }

    public function read(Theme $theme, string $path): string
    {
        return (string) file_get_contents($this->resolve($theme, $path, mustExist: true));
    }

    /**
     * Uloží súbor (aj nový). Šablóna Blade sa pred uložením skontroluje, aby chyba nezhodila obchod.
     *
     * @throws RuntimeException
     */
    public function write(Theme $theme, string $path, string $content): void
    {
        if (! $this->canEdit($theme)) {
            throw new RuntimeException(__('admin.theme_editor.errors.default_theme'));
        }

        $file = $this->resolve($theme, $path, mustExist: false);
        $this->validate($path, $content);

        if (is_file($file)) {
            $this->backup($theme, $path, (string) file_get_contents($file));
        }

        File::ensureDirectoryExists(dirname($file));
        file_put_contents($file, $content);

        if (str_ends_with($path, '.json')) {
            $this->themes->flush();
        }
    }

    /**
     * Zálohy súboru od najnovšej: časová značka => dátum.
     *
     * @return array<string, string>
     */
    public function backups(Theme $theme, string $path): array
    {
        $directory = $this->backupDirectory($theme, $path);
        $backups = [];

        foreach (glob($directory.'/*.bak') ?: [] as $backup) {
            $stamp = basename($backup, '.bak');
            $backups[$stamp] = date('j. n. Y H:i:s', (int) substr($stamp, 0, 10));
        }

        krsort($backups);

        return $backups;
    }

    public function restore(Theme $theme, string $path, string $stamp): void
    {
        $backup = $this->backupDirectory($theme, $path).'/'.preg_replace('/[^0-9_]/', '', $stamp).'.bak';

        if (! is_file($backup)) {
            throw new RuntimeException(__('admin.theme_editor.errors.no_backup'));
        }

        $this->write($theme, $path, (string) file_get_contents($backup));
    }

    /**
     * Vytvorí odvodenú šablónu (preberá všetko od rodiča, úpravy sa robia v nej).
     *
     * @throws RuntimeException
     */
    public function createChild(Theme $parent, string $name, string $title): Theme
    {
        if (! preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
            throw new RuntimeException(__('admin.themes.errors.invalid_name'));
        }

        $path = dirname($parent->path).'/'.$name;

        if (file_exists($path)) {
            throw new RuntimeException(__('admin.themes.errors.exists', ['name' => $name]));
        }

        File::ensureDirectoryExists($path);
        file_put_contents($path.'/theme.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
            'parent' => $parent->name,
            'title' => [app()->getLocale() => $title],
            'description' => [app()->getLocale() => __('admin.theme_editor.child_description', ['parent' => $parent->title()])],
            'requires' => ['eshop' => $parent->requiredEshopVersion ?? '*'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");

        $this->themes->flush();

        return $this->themes->find($name) ?? throw new RuntimeException(__('admin.themes.errors.invalid_manifest'));
    }

    /**
     * Skopíruje súbor z rodiča do odvodenej šablóny, aby sa dal upraviť.
     */
    public function copyFromParent(Theme $theme, string $path): void
    {
        foreach (array_slice($this->themes->chainFor($theme), 1) as $parent) {
            $source = $parent->path.'/'.$path;

            if (is_file($source) && ! str_contains($path, '..')) {
                $this->write($theme, $path, (string) file_get_contents($source));

                return;
            }
        }

        throw new RuntimeException(__('admin.theme_editor.errors.not_found'));
    }

    /**
     * Súbory rodičovských šablón, ktoré sa dajú skopírovať na úpravu.
     *
     * @return list<string>
     */
    public function parentFiles(Theme $theme): array
    {
        $own = $this->files($theme);
        $files = [];

        foreach (array_slice($this->themes->chainFor($theme), 1) as $parent) {
            foreach ($this->files($parent) as $file) {
                if (str_ends_with($file, '.blade.php') && ! in_array($file, $own, true)) {
                    $files[$file] = true;
                }
            }
        }

        ksort($files);

        return array_keys($files);
    }

    private function validate(string $path, string $content): void
    {
        if (strlen($content) > self::MAX_SIZE) {
            throw new RuntimeException(__('admin.theme_editor.errors.too_large'));
        }

        if (str_ends_with($path, '.json') && json_decode($content) === null && trim($content) !== 'null') {
            throw new RuntimeException(__('admin.theme_editor.errors.invalid_json'));
        }

        if (str_ends_with($path, '.php')) {
            $code = str_ends_with($path, '.blade.php') ? Blade::compileString($content) : $content;

            try {
                token_get_all($code, TOKEN_PARSE);
            } catch (ParseError $e) {
                throw new RuntimeException(__('admin.theme_editor.errors.syntax', ['message' => $e->getMessage(), 'line' => $e->getLine()]));
            }
        }
    }

    private function backup(Theme $theme, string $path, string $content): void
    {
        $directory = $this->backupDirectory($theme, $path);
        File::ensureDirectoryExists($directory);
        file_put_contents($directory.'/'.time().'_'.substr((string) hrtime(true), -6).'.bak', $content);

        // Najviac 20 záloh na súbor.
        $backups = glob($directory.'/*.bak') ?: [];
        sort($backups);

        foreach (array_slice($backups, 0, max(0, count($backups) - 20)) as $old) {
            @unlink($old);
        }
    }

    private function backupDirectory(Theme $theme, string $path): string
    {
        return $this->backupPath.'/'.$theme->name.'/'.str_replace('/', '__', $path);
    }

    private function resolve(Theme $theme, string $path, bool $mustExist): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($path === '' || str_contains($path, '..') || str_contains($path, "\0") || ! in_array($extension, self::EDITABLE, true)
            || ! preg_match('#^[A-Za-z0-9_\-./]+$#', $path)) {
            throw new RuntimeException(__('admin.theme_editor.errors.invalid_path'));
        }

        $file = $theme->path.'/'.$path;

        if ($mustExist && ! is_file($file)) {
            throw new RuntimeException(__('admin.theme_editor.errors.not_found'));
        }

        return $file;
    }
}
