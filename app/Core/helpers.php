<?php

use App\Core\Blocks\BlockTypes;
use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use App\Core\Themes\ThemeManager;
use App\Models\BlockLayout;
use Illuminate\Support\HtmlString;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

if (! function_exists('format_price')) {
    /**
     * Naformátuje cenu pre zobrazenie v šablóne, napr. format_price(1290) → „12,90 €".
     *
     * @param  int  $amount  Suma v najmenších jednotkách meny (centy, haliere).
     */
    function format_price(int $amount, ?string $currency = null): string
    {
        return Price::format($amount, $currency ?? app(CurrencyContext::class)->current());
    }
}

if (! function_exists('theme_asset')) {
    /**
     * Adresa súboru z priečinka assets/ šablóny (predvolene aktívnej), napr. theme_asset('css/theme.css').
     */
    function theme_asset(string $path, ?string $theme = null): string
    {
        $theme ??= app(ThemeManager::class)->active()?->name ?? (string) config('eshop.default_theme');

        return route('theme.asset', ['theme' => $theme, 'path' => ltrim($path, '/')]);
    }
}

if (! function_exists('render_blocks')) {
    /**
     * Vykreslí bloky (napr. z úvodnej stránky, stránky alebo kategórie) šablónou blocks/{typ}.
     *
     * @param  list<array<string, mixed>>|null  $blocks
     */
    function render_blocks(?array $blocks): HtmlString
    {
        return app(BlockTypes::class)->render($blocks);
    }
}

if (! function_exists('block_area')) {
    /**
     * Vykreslí bloky oblasti šablóny (oblasti definuje theme.json, obsah sa vkladá v administrácii).
     */
    function block_area(string $area): HtmlString
    {
        return render_blocks(BlockLayout::blocksFor('area:'.$area));
    }
}

if (! function_exists('safe_html')) {
    /**
     * Obsah z editora (popisy, stránky, texty blokov) bez skriptov, obsluhy udalostí a odkazov javascript:.
     * Zachová bežné formátovanie, obrázky, tabuľky, odkazy aj vložené videá z YouTube a Vimeo.
     */
    function safe_html(?string $html): HtmlString
    {
        static $sanitizer = null;

        $sanitizer ??= new HtmlSanitizer(
            (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowElement('iframe', ['src', 'width', 'height', 'title', 'allow', 'allowfullscreen', 'frameborder', 'loading'])
                ->allowAttribute('class', '*')
                ->allowAttribute('style', '*')
                ->allowAttribute('target', 'a')
                ->allowAttribute('rel', 'a')
                ->allowRelativeLinks()
                ->allowRelativeMedias()
                ->forceAttribute('a', 'rel', 'noopener')
                ->withMaxInputLength(1_000_000)
        );

        $clean = $sanitizer->sanitize((string) $html);

        // Iframe iba z overených služieb na videá.
        $clean = preg_replace_callback('/<iframe\b[^>]*>.*?<\/iframe>/is', function (array $match): string {
            return preg_match('/src="https:\/\/(www\.)?(youtube\.com|youtube-nocookie\.com|player\.vimeo\.com)\//i', $match[0]) ? $match[0] : '';
        }, $clean) ?? '';

        return new HtmlString($clean);
    }
}
