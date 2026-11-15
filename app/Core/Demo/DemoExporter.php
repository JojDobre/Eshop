<?php

namespace App\Core\Demo;

use App\Core\Eshop;
use App\Core\Modules\ModuleManager;
use App\Core\Themes\ThemeManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;

/**
 * Export obsahu obchodu ako demo balík: produkty, kategórie, stránky, blog, bloky, formuláre, menu,
 * nastavenia šablóny a logo, spolu s obrázkami. Balík je priečinok alebo ZIP s demo.json a media/.
 */
class DemoExporter
{
    /** @var array<string, true> */
    private array $media = [];

    /**
     * Vytvorí ZIP s demom a vráti cestu k nemu.
     *
     * @param  array<string, string>  $title  Názov dema v jazykoch.
     */
    public function exportZip(array $title = []): string
    {
        $directory = storage_path('app/private/demo-exports/'.Str::random(12));
        $this->exportDirectory($directory, $title);

        $zipPath = $directory.'.zip';
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Cannot create '.$zipPath);
        }

        foreach (File::allFiles($directory) as $file) {
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $file->getRelativePathname()));
        }

        $zip->close();
        File::deleteDirectory($directory);

        return $zipPath;
    }

    /**
     * Zapíše demo do priečinka (demo.json + media/). Používa sa aj na demá dodávané so šablónou.
     *
     * @param  array<string, string>  $title
     */
    public function exportDirectory(string $directory, array $title = []): void
    {
        $this->media = [];
        File::ensureDirectoryExists($directory);

        $tables = [];

        foreach (array_keys(DemoSchema::TABLES) as $table) {
            if (Schema::hasTable($table)) {
                $query = DB::table($table)->orderBy('id');

                // Typy produktov iba tie, ktoré produkty v deme používajú.
                if ($table === 'product_types') {
                    $query->whereIn('id', DB::table('products')->whereNotNull('product_type_id')->distinct()->pluck('product_type_id'));
                }

                $tables[$table] = $this->rows($query->get());
            }
        }

        $pivots = [];

        foreach (array_keys(DemoSchema::PIVOTS) as $table) {
            if (Schema::hasTable($table)) {
                $pivots[$table] = $this->rows(DB::table($table)->get());
            }
        }

        $theme = app(ThemeManager::class)->activeName();
        $themeSettings = Schema::hasTable('theme_settings')
            ? DB::table('theme_settings')->where('theme', $theme)->value('values')
            : null;

        $settings = [];

        foreach (DemoSchema::SETTINGS_GROUPS as $group) {
            foreach (DB::table('settings')->where('group', $group)->get(['name', 'payload']) as $row) {
                $settings[$group][$row->name] = $this->value($row->payload);
            }
        }

        $data = [
            'format' => DemoSchema::FORMAT,
            'eshop' => Eshop::VERSION,
            'title' => $title,
            'theme' => $theme,
            // Moduly, ktoré demo potrebuje (pri importe sa zapnú).
            'modules' => array_values(array_filter(['reservations', 'blog', 'forms'], fn (string $module) => app(ModuleManager::class)->isActive($module) && $this->moduleHasContent($module))),
            'created_at' => now()->toIso8601String(),
            'tables' => $tables,
            'pivots' => $pivots,
            'theme_settings' => $themeSettings === null ? null : $this->value($themeSettings),
            'settings' => $settings,
        ];

        File::put($directory.'/demo.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $disk = Storage::disk('public');

        foreach (array_keys($this->media) as $path) {
            if ($disk->exists($path)) {
                File::ensureDirectoryExists(dirname($directory.'/media/'.$path));
                File::copy($disk->path($path), $directory.'/media/'.$path);
            }
        }
    }

    private function moduleHasContent(string $module): bool
    {
        $table = ['reservations' => 'booking_services', 'blog' => 'blog_posts', 'forms' => 'forms'][$module];

        return Schema::hasTable($table) && DB::table($table)->exists();
    }

    /**
     * @param  iterable<object>  $rows
     * @return list<array<string, mixed>>
     */
    private function rows(iterable $rows): array
    {
        $result = [];

        foreach ($rows as $row) {
            $row = (array) $row;

            foreach (DemoSchema::SKIPPED_COLUMNS as $column) {
                if ($column !== 'id') {
                    unset($row[$column]);
                }
            }

            $result[] = array_map(fn ($value) => $this->value($value), $row);
        }

        return $result;
    }

    /**
     * Upraví hodnotu: adresy nahraných súborov nahradí zástupným textom a zapamätá si použité obrázky.
     */
    private function value(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = ltrim($value);

        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[' || $trimmed[0] === '"')) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($this->walk($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $this->string($value);
    }

    private function walk(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->walk($item), $value);
        }

        return is_string($value) ? $this->string($value) : $value;
    }

    private function string(string $value): string
    {
        $uploads = rtrim(Storage::disk('public')->url(''), '/').'/';
        $value = str_replace($uploads, DemoSchema::UPLOADS_PLACEHOLDER, $value);
        // Odkazy na stránky tohto obchodu ako relatívne adresy, aby fungovali aj na inej doméne.
        $value = str_replace(rtrim(url('/'), '/').'/', '/', $value);

        if (preg_match_all(DemoSchema::MEDIA_PATTERN, $value, $matches)) {
            foreach ($matches[1] as $path) {
                $this->media[$path] = true;
            }
        }

        return $value;
    }
}
