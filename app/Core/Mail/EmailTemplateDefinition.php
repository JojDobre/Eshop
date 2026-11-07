<?php

namespace App\Core\Mail;

/**
 * Popis upraviteľného e-mailu, ktorý registruje modul.
 */
final class EmailTemplateDefinition
{
    /**
     * @param  string  $key  Napr. „orders.confirmation".
     * @param  string  $translationKey  Kľúč prekladov s predvolenými textami: {key}.label, .subject, .body.
     * @param  list<string>  $variables  Premenné, ktoré sa dajú v texte použiť (bez zátvoriek).
     * @param  bool  $canBeDisabled  Dá sa e-mail vypnúť (napr. upozornenie na zmenu stavu)?
     * @param  array<string, string>  $sample  Ukážkové hodnoty premenných pre náhľad a testovací e-mail.
     */
    public function __construct(
        public readonly string $key,
        public readonly string $translationKey,
        public readonly array $variables,
        public readonly bool $canBeDisabled = false,
        public readonly array $sample = [],
    ) {}

    public function label(): string
    {
        return __($this->translationKey.'.label');
    }

    public function defaultSubject(?string $locale = null): string
    {
        return __($this->translationKey.'.subject', [], $locale);
    }

    public function defaultBody(?string $locale = null): string
    {
        return __($this->translationKey.'.body', [], $locale);
    }
}
