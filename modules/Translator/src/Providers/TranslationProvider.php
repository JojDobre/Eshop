<?php

namespace Modules\Translator\Providers;

/**
 * Poskytovateľ strojového prekladu.
 */
interface TranslationProvider
{
    /**
     * @param  list<string>  $texts  Texty (môžu obsahovať HTML).
     * @return list<string> Preklady v rovnakom poradí.
     */
    public function translate(array $texts, string $from, string $to): array;

    /** Označí pojem, ktorý sa nemá prekladať (slovník). */
    public function protect(string $term): string;

    /** Odstráni označenie pojmov z prekladu. */
    public function unprotect(string $text): string;
}
