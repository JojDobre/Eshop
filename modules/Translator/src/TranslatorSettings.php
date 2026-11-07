<?php

namespace Modules\Translator;

use Spatie\LaravelSettings\Settings;

class TranslatorSettings extends Settings
{
    /** none, deepl, google */
    public string $provider;

    public ?string $api_key;

    /** Mesačný limit preložených znakov (0 = bez limitu). */
    public int $monthly_limit;

    /** Spotreba v aktuálnom mesiaci. */
    public int $used_characters;

    /** Mesiac spotreby (RRRR-MM). */
    public ?string $usage_month;

    /**
     * Slovník: [{term: „Kaviareň u Jožka“, translations: {en: „…“}}]. Bez prekladu sa pojem neprekladá.
     * (Bez typu v docbloku – spatie/laravel-settings by podľa neho pretypovával vnorené polia.)
     */
    public array $glossary;

    public static function group(): string
    {
        return 'translator';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['api_key'];
    }
}
