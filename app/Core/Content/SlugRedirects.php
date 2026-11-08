<?php

namespace App\Core\Content;

use App\Models\Redirect;
use App\Settings\SeoSettings;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Automatické presmerovania 301 pri zmene adresy (slug) produktu, kategórie, stránky či článku.
 */
final class SlugRedirects
{
    public static function enabled(): bool
    {
        return (bool) SeoSettings::current()?->auto_redirects;
    }

    /**
     * Adresa záznamu v každom jazyku obchodu.
     *
     * @return array<string, string>
     */
    public static function urls(Model $model): array
    {
        try {
            $urls = [];
            $localized = app(LocalizedUrls::class);
            $content = app(ContentLocale::class);
            $original = $content->current();

            try {
                foreach (app(ShopLocales::class)->enabled() as $locale) {
                    $content->set($locale);
                    $urls[$locale] = $localized->in($locale, fn () => $model->url());
                }
            } finally {
                $content->set($original);
            }

            return $urls;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, string>  $before
     * @param  array<string, string>  $after
     */
    public static function record(array $before, array $after): void
    {
        foreach ($before as $locale => $old) {
            if (isset($after[$locale]) && $after[$locale] !== $old) {
                Redirect::record($old, $after[$locale]);
            }
        }
    }
}
