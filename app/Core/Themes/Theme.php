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
     * @return array<string, string>
     */
    private static function strings(mixed $value): array
    {
        return is_array($value) ? array_filter($value, fn ($v, $k) => is_string($k) && is_string($v), ARRAY_FILTER_USE_BOTH) : [];
    }
}
