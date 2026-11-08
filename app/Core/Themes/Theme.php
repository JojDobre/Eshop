<?php

namespace App\Core\Themes;

/**
 * Popis šablóny vzhľadu načítaný zo súboru theme.json.
 */
final class Theme
{
    /**
     * @param  array<string, string>  $title
     * @param  array<string, string>  $description
     * @param  array<string, array<string, mixed>>  $settings  Nastavenia šablóny: kľúč => {type, label, default, options}.
     * @param  array<string, array<string, string>>  $menus  Miesta na ponuky: kľúč => názov podľa jazyka.
     * @param  array<string, array<string, string>>  $areas  Oblasti na bloky: kľúč => názov podľa jazyka.
     * @param  list<string>  $vite  Vstupy Vite (iba šablóny dodávané s obchodom, skompilované pri vydaní).
     * @param  list<string>  $stylesheets  Hotové CSS súbory v assets/ (pre nahraté šablóny, bez kompilácie).
     * @param  list<string>  $scripts  Hotové JS súbory v assets/.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $path,
        public readonly array $title = [],
        public readonly array $description = [],
        public readonly ?string $author = null,
        public readonly ?string $parent = null,
        public readonly ?string $requiredEshopVersion = null,
        public readonly ?string $screenshot = null,
        public readonly array $settings = [],
        public readonly array $menus = [],
        public readonly array $areas = [],
        public readonly array $vite = [],
        public readonly array $stylesheets = [],
        public readonly array $scripts = [],
    ) {}

    public static function fromFile(string $manifestPath): ?self
    {
        $data = json_decode((string) @file_get_contents($manifestPath), true);

        if (! is_array($data) || ! is_string($data['name'] ?? null) || ! preg_match('/^[a-z][a-z0-9-]*$/', $data['name'])) {
            return null;
        }

        $path = dirname($manifestPath);
        $screenshot = is_string($data['screenshot'] ?? null) ? $data['screenshot'] : null;

        return new self(
            name: $data['name'],
            version: is_string($data['version'] ?? null) ? $data['version'] : '0.0.0',
            path: $path,
            title: self::strings($data['title'] ?? []),
            description: self::strings($data['description'] ?? []),
            author: is_string($data['author'] ?? null) ? $data['author'] : null,
            parent: is_string($data['parent'] ?? null) ? $data['parent'] : null,
            requiredEshopVersion: is_string($data['requires']['eshop'] ?? null) ? $data['requires']['eshop'] : null,
            screenshot: $screenshot !== null && is_file($path.'/'.$screenshot) ? $screenshot : null,
            settings: self::settings($data['settings'] ?? []),
            menus: self::labels($data['menus'] ?? []),
            areas: self::labels($data['areas'] ?? []),
            vite: self::paths($data['vite'] ?? []),
            stylesheets: self::paths($data['stylesheets'] ?? []),
            scripts: self::paths($data['scripts'] ?? []),
        );
    }

    public function title(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->title[$locale] ?? $this->title['en'] ?? $this->name;
    }

    public function description(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return $this->description[$locale] ?? $this->description['en'] ?? '';
    }

    /**
     * Názov v jazyku (pre nastavenia, ponuky, oblasti).
     *
     * @param  array<string, string>  $labels
     */
    public static function label(array $labels, ?string $fallback = null): string
    {
        $locale = app()->getLocale();

        return $labels[$locale] ?? $labels['en'] ?? (reset($labels) ?: (string) $fallback);
    }

    /**
     * Platné definície nastavení: typ color, select, toggle, text, number alebo image.
     *
     * @return array<string, array<string, mixed>>
     */
    private static function settings(mixed $value): array
    {
        $settings = [];

        foreach (is_array($value) ? $value : [] as $key => $setting) {
            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]*$/', $key) || ! is_array($setting)
                || ! in_array($setting['type'] ?? null, ['color', 'select', 'toggle', 'text', 'number', 'image'], true)) {
                continue;
            }

            $settings[$key] = [
                'type' => $setting['type'],
                'label' => self::strings($setting['label'] ?? []),
                'help' => self::strings($setting['help'] ?? []),
                'default' => $setting['default'] ?? null,
                'options' => array_map(fn ($labels) => is_array($labels) ? self::strings($labels) : ['en' => (string) $labels], is_array($setting['options'] ?? null) ? $setting['options'] : []),
                'group' => is_string($setting['group'] ?? null) ? $setting['group'] : null,
            ];
        }

        return $settings;
    }

    /**
     * @return list<string>
     */
    private static function paths(mixed $value): array
    {
        return array_values(array_filter(is_array($value) ? $value : [], fn ($path) => is_string($path) && $path !== '' && ! str_contains($path, '..')));
    }

    /**
     * @return array<string, array<string, string>>
     */
    private static function labels(mixed $value): array
    {
        $labels = [];

        foreach (is_array($value) ? $value : [] as $key => $names) {
            if (is_string($key) && preg_match('/^[a-z][a-z0-9_-]*$/', $key)) {
                $labels[$key] = is_array($names) ? self::strings($names) : [];
            }
        }

        return $labels;
    }

    /**
     * @return array<string, string>
     */
    private static function strings(mixed $value): array
    {
        return is_array($value) ? array_filter($value, fn ($v, $k) => is_string($k) && is_string($v), ARRAY_FILTER_USE_BOTH) : [];
    }
}
