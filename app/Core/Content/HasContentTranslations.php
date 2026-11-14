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
     * Náhradný jazyk, keď text v požadovanom jazyku chýba: najprv predvolený jazyk obchodu, potom ktorýkoľvek
     * vyplnený jazyk. Obsah tak nezmizne po pridaní jazyka ani po zmene predvoleného jazyka obchodu.
     * (Administrácia číta texty bez náhrady, aby bolo vidno, čo treba preložiť.)
     */
    protected function normalizeLocale(string $key, string $locale, bool $useFallbackLocale): string
    {
        $translated = $this->getTranslatedLocales($key);

        if (! $useFallbackLocale || in_array($locale, $translated, true)) {
            return $locale;
        }

        $default = app(ContentLocale::class)->default();

        return in_array($default, $translated, true) ? $default : ($translated[0] ?? $locale);
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
