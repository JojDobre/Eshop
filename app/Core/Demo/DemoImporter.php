<?php

namespace App\Core\Demo;

use App\Core\Backup\BackupManager;
use App\Core\Content\MediaLibrary;
use App\Core\Themes\ThemeManager;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

/**
 * Import demo balíka do obchodu. Pred importom sa vytvorí záloha databázy.
 *
 * Režim „pridať“ doplní obsah k existujúcemu, režim „nahradiť“ najprv zmaže produkty, kategórie, blog, vlastné
 * stránky, bloky, formuláre a menu (iba v obchode bez objednávok). Právne stránky (podmienky, GDPR…) sa nemažú,
 * demo im môže zmeniť iba obsah.
 */
class DemoImporter
{
    /** @var array<string, array<int|string, int>> stará ID → nové ID podľa tabuľky */
    private array $map = [];

    private string $uploadsUrl = '';

    public function __construct(private readonly BackupManager $backups) {}

    /**
     * Názov a téma dema bez importu (náhľad pred potvrdením).
     *
     * @return array{title: array<string, string>, theme: ?string, counts: array<string, int>}
     */
    public function inspect(string $path): array
    {
        [$data] = $this->load($path);

        return [
            'title' => (array) ($data['title'] ?? []),
            'theme' => $data['theme'] ?? null,
            'counts' => collect($data['tables'] ?? [])->map(fn ($rows) => count((array) $rows))->filter()->all(),
        ];
    }

    /** Či sa dá použiť režim „nahradiť“ (obchod ešte nemá objednávky). */
    public function canReplace(): bool
    {
        return ! Schema::hasTable('orders') || ! DB::table('orders')->exists();
    }

    /**
     * Nahrá demo zo ZIP-u alebo priečinka. Vráti počty pridaných záznamov podľa tabuliek.
     *
     * @return array<string, int>
     */
    public function import(string $path, bool $replace = false, bool $activateTheme = true, bool $backup = true): array
    {
        [$data, $directory, $temporary] = $this->load($path);

        if ($replace && ! $this->canReplace()) {
            throw new DemoException(__('admin.demo.replace_has_orders'));
        }

        if ($backup) {
            $this->backups->create(false);
        }

        @set_time_limit(0);
        $this->map = [];
        $this->uploadsUrl = rtrim(Storage::disk('public')->url(''), '/').'/';
        $counts = [];

        try {
            $this->copyMedia($directory);

            DB::transaction(function () use ($data, $replace, &$counts): void {
                if ($replace) {
                    $this->clear();
                }

                foreach (DemoSchema::TABLES as $table => $references) {
                    $rows = (array) ($data['tables'][$table] ?? []);

                    if ($rows !== [] && Schema::hasTable($table)) {
                        $counts[$table] = $this->insertRows($table, $rows, $references, $replace);
                    }
                }

                $this->fixSelfReferences($data);
                $this->fixMenuTargets();

                foreach (DemoSchema::PIVOTS as $table => $references) {
                    if (Schema::hasTable($table)) {
                        $this->insertPivots($table, (array) ($data['pivots'][$table] ?? []), $references);
                    }
                }

                $this->importSettings($data);
            });
        } finally {
            if ($temporary) {
                File::deleteDirectory($directory);
            }
        }

        $theme = (string) ($data['theme'] ?? '');

        if ($activateTheme && $theme !== '' && app(ThemeManager::class)->find($theme)) {
            $settings = app(GeneralSettings::class);
            $settings->theme = $theme;
            $settings->save();
        }

        if (is_array($data['theme_settings'] ?? null) || is_string($data['theme_settings'] ?? null)) {
            $this->importThemeSettings($theme !== '' ? $theme : app(ThemeManager::class)->activeName(), $data['theme_settings']);
        }

        rescue(fn () => app(MediaLibrary::class)->sync(), report: true);
        rescue(fn () => Artisan::call('cache:clear'), report: false);
        Cache::flush();

        return $counts;
    }

    /**
     * @return array{0: array<string, mixed>, 1: string, 2: bool}
     */
    private function load(string $path): array
    {
        $temporary = false;
        $directory = $path;

        if (is_file($path)) {
            $directory = storage_path('app/private/demo-imports/'.Str::random(12));
            $zip = new ZipArchive;

            if ($zip->open($path) !== true) {
                throw new DemoException(__('admin.demo.invalid'));
            }

            // Iba bezpečné cesty (demo.json a media/…), nič mimo priečinka.
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = (string) $zip->getNameIndex($i);

                if (str_contains($name, '..') || str_starts_with($name, '/') || ! ($name === 'demo.json' || str_starts_with($name, 'media/'))) {
                    continue;
                }

                if (str_ends_with($name, '/')) {
                    continue;
                }

                if (str_starts_with($name, 'media/') && ! preg_match('/\.(jpe?g|png|webp|gif|svg|avif)$/i', $name)) {
                    continue;
                }

                File::ensureDirectoryExists(dirname($directory.'/'.$name));
                File::put($directory.'/'.$name, (string) $zip->getFromIndex($i));
            }

            $zip->close();
            $temporary = true;
        }

        $json = is_file($directory.'/demo.json') ? json_decode((string) File::get($directory.'/demo.json'), true) : null;

        if (! is_array($json) || (int) ($json['format'] ?? 0) !== DemoSchema::FORMAT || ! is_array($json['tables'] ?? null)) {
            if ($temporary) {
                File::deleteDirectory($directory);
            }

            throw new DemoException(__('admin.demo.invalid'));
        }

        return [$json, $directory, $temporary];
    }

    private function copyMedia(string $directory): void
    {
        if (! is_dir($directory.'/media')) {
            return;
        }

        $disk = Storage::disk('public');

        foreach (File::allFiles($directory.'/media') as $file) {
            $relative = str_replace('\\', '/', $file->getRelativePathname());

            if (! $disk->exists($relative)) {
                $disk->put($relative, File::get($file->getPathname()));
            }
        }
    }

    /** Zmaže obsah, ktorý demo nahrádza (cudzie kľúče sa počas mazania nekontrolujú). */
    private function clear(): void
    {
        $tables = [
            'category_product', 'product_variant_values', 'addon_product', 'product_label_product', 'blog_post_tag', 'blog_post_product',
            'blog_post_catalog_category', 'product_prices', 'product_group_prices', 'product_quantity_discounts', 'product_price_history',
            'product_variants', 'product_option_values', 'product_options', 'product_files', 'product_reviews', 'stock_movements', 'stock_lots',
            'bundle_items', 'cart_items', 'products', 'categories', 'manufacturers', 'product_labels', 'addons', 'blog_comments',
            'blog_post_versions', 'blog_posts', 'blog_categories', 'blog_tags', 'saved_blocks', 'block_layouts', 'menu_items',
        ];

        Schema::withoutForeignKeyConstraints(function () use ($tables): void {
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            if (Schema::hasTable('custom_field_values')) {
                DB::table('custom_field_values')->whereIn('entity_type', ['product', 'category'])->delete();
            }

            if (Schema::hasTable('form_submissions')) {
                DB::table('form_submissions')->delete();
            }

            if (Schema::hasTable('forms')) {
                DB::table('forms')->delete();
            }

            if (Schema::hasTable('page_versions')) {
                DB::table('page_versions')->whereIn('page_id', DB::table('pages')->whereNull('role')->pluck('id'))->delete();
            }

            DB::table('pages')->whereNull('role')->delete();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string|null>  $references
     */
    private function insertRows(string $table, array $rows, array $references, bool $replace): int
    {
        $columns = array_flip(Schema::getColumnListing($table));
        $count = 0;

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $oldId = $row['id'] ?? null;
            $row = array_intersect_key(array_map(fn ($value) => $this->restore($value), $row), $columns);

            foreach (DemoSchema::SKIPPED_COLUMNS as $column) {
                unset($row[$column]);
            }

            // Tajný odkaz na náhľad konceptu sa nikdy neprenáša, vytvorí sa nový.
            if (isset($columns['preview_token'])) {
                $row['preview_token'] = Str::random(40);
            }

            foreach ($references as $column => $target) {
                if (! array_key_exists($column, $row) || $row[$column] === null) {
                    continue;
                }

                // Odkaz na seba sa doplní až po vložení všetkých riadkov.
                $row[$column] = $target === null || $target === $table ? null : ($this->map[$target][$row[$column]] ?? null);
            }

            foreach (DemoSchema::JSON_COLUMNS_WITH_REFERENCES as $column) {
                if (is_string($row[$column] ?? null)) {
                    $row[$column] = $this->remapJson($row[$column]);
                }
            }

            // Stránky s rolou (kontakt, podmienky…) sa neduplikujú – demo zmení iba ich obsah.
            if ($table === 'pages' && filled($row['role'] ?? null)) {
                $existing = DB::table('pages')->where('role', $row['role'])->value('id');

                if ($existing) {
                    DB::table('pages')->where('id', $existing)->update(array_intersect_key($row, array_flip(['content', 'blocks', 'display'])) + ['updated_at' => now()]);
                    $this->map[$table][$oldId] = (int) $existing;

                    continue;
                }
            }

            if ($table === 'block_layouts') {
                DB::table('block_layouts')->where('key', $row['key'] ?? '')->delete();
            }

            if (! $replace && array_key_exists('slug', $row) && is_string($row['slug'])) {
                $row['slug'] = $this->uniqueSlug($table, $row['slug']);
            }

            $row += array_intersect_key(['created_at' => now(), 'updated_at' => now()], $columns);
            $newId = DB::table($table)->insertGetId($row);

            if ($oldId !== null) {
                $this->map[$table][$oldId] = (int) $newId;
            }

            $count++;
        }

        return $count;
    }

    /**
     * Doplní odkazy na riadky tej istej tabuľky (nadradená kategória, položka menu).
     *
     * @param  array<string, mixed>  $data
     */
    private function fixSelfReferences(array $data): void
    {
        foreach (DemoSchema::TABLES as $table => $references) {
            foreach ($references as $column => $target) {
                if ($target !== $table) {
                    continue;
                }

                foreach ((array) ($data['tables'][$table] ?? []) as $row) {
                    $old = $row['id'] ?? null;
                    $parent = $row[$column] ?? null;

                    if ($old !== null && $parent !== null && isset($this->map[$table][$old], $this->map[$table][$parent])) {
                        DB::table($table)->where('id', $this->map[$table][$old])->update([$column => $this->map[$table][$parent]]);
                    }
                }
            }
        }
    }

    private function fixMenuTargets(): void
    {
        foreach ($this->map['menu_items'] ?? [] as $newId) {
            $item = DB::table('menu_items')->where('id', $newId)->first(['type', 'target']);
            $targetTable = DemoSchema::MENU_TARGETS[$item->type ?? ''] ?? null;

            if ($targetTable !== null && $item->target !== null) {
                DB::table('menu_items')->where('id', $newId)->update(['target' => $this->map[$targetTable][$item->target] ?? null]);
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  array<string, string>  $references
     */
    private function insertPivots(string $table, array $rows, array $references): void
    {
        $columns = array_flip(Schema::getColumnListing($table));
        $insert = [];

        foreach ($rows as $row) {
            $row = array_intersect_key((array) $row, $columns);

            foreach ($references as $column => $target) {
                $row[$column] = $this->map[$target][$row[$column] ?? ''] ?? null;

                if ($row[$column] === null) {
                    continue 2;
                }
            }

            $insert[] = $row;
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table($table)->insertOrIgnore($chunk);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function importSettings(array $data): void
    {
        foreach ((array) ($data['settings'] ?? []) as $group => $values) {
            if (! in_array($group, DemoSchema::SETTINGS_GROUPS, true)) {
                continue;
            }

            foreach ((array) $values as $name => $payload) {
                DB::table('settings')->updateOrInsert(['group' => $group, 'name' => $name], ['payload' => $this->restore($payload)]);
            }
        }
    }

    private function importThemeSettings(string $theme, mixed $values): void
    {
        if (! Schema::hasTable('theme_settings')) {
            return;
        }

        $values = $this->remapJson((string) $this->restore(is_string($values) ? $values : json_encode($values)));
        $exists = DB::table('theme_settings')->where('theme', $theme)->exists();

        $exists
            ? DB::table('theme_settings')->where('theme', $theme)->update(['values' => $values, 'updated_at' => now()])
            : DB::table('theme_settings')->insert(['theme' => $theme, 'values' => $values, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** Vráti adresy nahraných súborov (zástupný text → adresa tohto obchodu). */
    private function restore(mixed $value): mixed
    {
        return is_string($value) ? str_replace(DemoSchema::UPLOADS_PLACEHOLDER, $this->uploadsUrl ?: rtrim(Storage::disk('public')->url(''), '/').'/', $value) : $value;
    }

    private function remapJson(string $json): string
    {
        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return $json;
        }

        return (string) json_encode($this->remapReferences($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<mixed>  $data
     * @return array<mixed>
     */
    private function remapReferences(array $data): array
    {
        foreach ($data as $key => $value) {
            $table = is_string($key) ? (DemoSchema::BLOCK_REFERENCES[$key] ?? null) : null;

            if ($table !== null && $value !== null && $value !== '') {
                $data[$key] = is_array($value)
                    ? array_values(array_filter(array_map(fn ($id) => $this->map[$table][$id] ?? null, $value)))
                    : ($this->map[$table][$value] ?? null);
            } elseif (is_array($value)) {
                $data[$key] = $this->remapReferences($value);
            }
        }

        return $data;
    }

    /**
     * Pri pridávaní k existujúcemu obsahu zabráni rovnakým adresám (slug v každom jazyku).
     */
    private function uniqueSlug(string $table, string $slugs): string
    {
        $decoded = json_decode($slugs, true);

        if (! is_array($decoded)) {
            return $slugs;
        }

        foreach ($decoded as $locale => $slug) {
            if (! is_string($slug) || $slug === '') {
                continue;
            }

            $candidate = $slug;
            $suffix = 2;

            try {
                while (DB::table($table)->where('slug->'.$locale, $candidate)->exists()) {
                    $candidate = $slug.'-'.$suffix++;
                }
            } catch (Throwable) {
                // Databáza bez podpory JSON ciest – ponechá sa pôvodná adresa.
            }

            $decoded[$locale] = $candidate;
        }

        return (string) json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
}
