<?php

namespace App\Core\Content;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Jedinečná adresa (slug) v jazyku obsahu, vytvorená z názvu, ak ju administrátor nezadá.
 *
 * Model musí mať preložiteľné stĺpce „name" a „slug" (HasContentTranslations).
 */
trait HasLocalizedSlug
{
    public static function bootHasLocalizedSlug(): void
    {
        static::saving(function (self $model): void {
            $locale = $model->getLocale();
            $slug = Str::slug((string) ($model->getTranslation('slug', $locale, false) ?: $model->getTranslation('name', $locale, false)));

            $model->setTranslation('slug', $locale, $model->uniqueSlug($slug !== '' ? $slug : 'item', $locale));
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWhereSlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        return $query->where('slug->'.($locale ?? $this->getLocale()), $slug);
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
