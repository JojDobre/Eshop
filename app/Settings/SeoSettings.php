<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SeoSettings extends Settings
{
    public string $product_title;

    public ?string $product_description;

    public string $category_title;

    public ?string $category_description;

    public bool $auto_redirects;

    public bool $noindex_filtered;

    public ?string $robots_extra;

    public static function group(): string
    {
        return 'seo';
    }

    /**
     * Vyplní šablónu premennými ({name}, {shop}…); prázdna šablóna = null.
     *
     * @param  array<string, string|null>  $variables
     */
    public static function render(?string $template, array $variables): ?string
    {
        if (blank($template)) {
            return null;
        }

        $text = strtr((string) $template, collect($variables)->mapWithKeys(fn ($value, $key) => ['{'.$key.'}' => (string) $value])->all());
        // Zvyšné oddeľovače po prázdnych premenných („Káva |  | Obchod“ → „Káva | Obchod“).
        $text = trim((string) preg_replace(['/\s*([|·–-])\s*(?=[|·–-]|$)/u', '/\s{2,}/'], ['', ' '], $text));

        return $text !== '' ? $text : null;
    }

    public static function current(): ?self
    {
        try {
            return app(self::class);
        } catch (\Throwable) {
            return null;
        }
    }
}
