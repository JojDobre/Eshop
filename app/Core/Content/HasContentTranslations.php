<?php

namespace App\Core\Content;

use Spatie\Translatable\HasTranslations;

/**
 * Preložiteľné stĺpce modelu (JSON podľa jazyka, napr. {"sk": "Káva"}).
 *
 * Na rozdiel od samotného balíka spatie/laravel-translatable sa ako predvolený jazyk
 * použije jazyk obsahu obchodu, nie jazyk rozhrania (pozri ContentLocale).
 */
trait HasContentTranslations
{
    use HasTranslations;

    public function getLocale(): string
    {
        return $this->translationLocale ?: app(ContentLocale::class)->current();
    }

    /**
     * Jazyk, do ktorého sa zapisujú a z ktorého sa čítajú preložiteľné stĺpce (null = jazyk obsahu).
     */
    public function useContentLocale(?string $locale): static
    {
        $this->translationLocale = $locale;

        return $this;
    }
}
