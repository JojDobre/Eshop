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
}
