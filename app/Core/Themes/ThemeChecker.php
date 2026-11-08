<?php

namespace App\Core\Themes;

/**
 * Kontrola šablóny: povinné súčasti (lišta cookies, právne odkazy) a základná prístupnosť.
 */
class ThemeChecker
{
    /** Stránky, ktoré musí obchod vedieť zobraziť (v šablóne alebo u rodiča). */
    public const REQUIRED_TEMPLATES = ['layouts/app', 'templates/home', 'templates/category', 'templates/product', 'templates/cart', 'templates/checkout', 'templates/order', 'templates/page', 'templates/search'];

    public function __construct(private readonly ThemeManager $themes) {}

    /**
     * @return list<array{level: string, message: string}> level: error, warning, ok
     */
    public function check(Theme $theme): array
    {
        $chain = $this->themes->chainFor($theme);
        $results = [];
        $add = function (string $level, string $key, array $replace = []) use (&$results): void {
            $results[] = ['level' => $level, 'message' => __('admin.theme_check.'.$key, $replace)];
        };

        if ($theme->requiredEshopVersion === null) {
            $add('warning', 'no_requires');
        } elseif (! ThemePackages::compatible($theme->requiredEshopVersion)) {
            $add('error', 'incompatible', ['required' => $theme->requiredEshopVersion]);
        }

        if ($theme->parent !== null && count($chain) < 2) {
            $add('error', 'missing_parent', ['parent' => $theme->parent]);
        }

        foreach (self::REQUIRED_TEMPLATES as $template) {
            if ($this->find($chain, $template) === null && ! $this->defaultHas($template)) {
                $add('error', 'missing_template', ['template' => $template]);
            }
        }

        $layout = (string) $this->contents($chain, 'layouts/app');
        $all = $layout.$this->contents($chain, 'partials/footer').$this->contents($chain, 'partials/header');

        $this->has($layout, ['cookie-bar', '$cookieConsent']) ? $add('ok', 'cookies_ok') : $add('error', 'cookies_missing');
        $this->has($all, ['$legalPages', '$footerPages', "items('footer')"]) ? $add('ok', 'legal_ok') : $add('warning', 'legal_missing');
        $this->has($layout, ['<html lang=', 'lang="{{']) ? $add('ok', 'lang_ok') : $add('warning', 'lang_missing');
        $this->has($layout, ['#content', 'skip_to_content']) ? $add('ok', 'skip_ok') : $add('warning', 'skip_missing');

        // Obrázky bez alternatívneho textu vo vlastných súboroch šablóny.
        foreach (glob($theme->path.'/{layouts,templates,partials,blocks}/*.blade.php', GLOB_BRACE) ?: [] as $file) {
            if (preg_match('/<img(?![^>]*\balt=)[^>]*>/i', (string) file_get_contents($file))) {
                $add('warning', 'img_alt', ['file' => str_replace($theme->path.'/', '', $file)]);
            }
        }

        return $results;
    }

    /**
     * @param  list<Theme>  $chain
     */
    private function find(array $chain, string $template): ?string
    {
        foreach ($chain as $item) {
            if (is_file($item->path.'/'.$template.'.blade.php')) {
                return $item->path.'/'.$template.'.blade.php';
            }
        }

        return null;
    }

    private function defaultHas(string $template): bool
    {
        $default = $this->themes->find((string) config('eshop.default_theme'));

        return $default !== null && is_file($default->path.'/'.$template.'.blade.php');
    }

    /**
     * @param  list<Theme>  $chain
     */
    private function contents(array $chain, string $template): string
    {
        $file = $this->find($chain, $template);

        return $file ? (string) file_get_contents($file) : '';
    }

    /**
     * @param  list<string>  $needles
     */
    private function has(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
