<?php

namespace App\Core\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Jedinečná adresa (slug) v každom jazyku, v ktorom má záznam názov. Ak ju administrátor
 * nezadá, vytvorí sa z názvu. Chýbajúci preklad adresy nahradí adresa v predvolenom jazyku.
 *
 * Model musí mať preložiteľné stĺpce „name" a „slug" (HasContentTranslations).
 */
trait HasLocalizedSlug
{
    /** @var array<string, string> Adresy pred zmenou slugu (pre automatické presmerovanie 301). */
    private array $urlsBeforeSlugChange = [];

    public static function bootHasLocalizedSlug(): void
    {
        // Zmena adresy: stará adresa v každom jazyku sa presmeruje na novú.
        static::updating(function (self $model): void {
            if ($model->isDirty('slug') && method_exists($model, 'url') && SlugRedirects::enabled()) {
                $original = (clone $model)->setRawAttributes($model->getRawOriginal());
                $model->urlsBeforeSlugChange = SlugRedirects::urls($original);
            }
        });
        static::updated(function (self $model): void {
            if ($model->urlsBeforeSlugChange !== []) {
                SlugRedirects::record($model->urlsBeforeSlugChange, SlugRedirects::urls($model));
                $model->urlsBeforeSlugChange = [];
            }
        });

        static::saving(function (self $model): void {
            $locales = array_unique([$model->getLocale(), ...array_keys(array_filter($model->getTranslations('name')))]);

            foreach ($locales as $locale) {
                $slug = Str::slug((string) ($model->getTranslation('slug', $locale, false) ?: $model->getTranslation('name', $locale, false)));

                if ($slug === '' && $locale !== $model->getLocale()) {
                    continue;
                }

                $model->setTranslation('slug', $locale, $model->uniqueSlug($slug !== '' ? $slug : 'item', $locale));
            }
        });
    }

    /**
     * Záznam podľa adresy v jazyku; ak v ňom adresa nie je, podľa adresy v predvolenom jazyku.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        $locale ??= $this->getLocale();
        $default = app(ShopLocales::class)->default();

        if ($locale === $default) {
            return $query->where('slug->'.$locale, $slug);
        }

        return $query->where(fn (Builder $query) => $query
            ->where('slug->'.$locale, $slug)
            ->orWhere(fn (Builder $query) => $query->whereNull('slug->'.$locale)->where('slug->'.$default, $slug)));
    }

    /** Adresa (slug) v danom jazyku, inak v predvolenom jazyku. */
    public function slugIn(string $locale): ?string
    {
        return $this->getTranslation('slug', $locale, false) ?: ($this->getTranslation('slug', app(ShopLocales::class)->default(), false) ?: null);
    }

    private function uniqueSlug(string $slug, string $locale): string
    {
        $candidate = $slug;
        $suffix = 2;

        while (static::query()
            ->where('slug->'.$locale, $candidate)
            ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $candidate = $slug.'-'.$suffix++;
        }

        return $candidate;
    }
}
