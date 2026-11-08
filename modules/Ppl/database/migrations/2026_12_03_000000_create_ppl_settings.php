<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Prístup do myAPI2 (client_id a client_secret pridelí PPL k zmluve).
        $this->migrator->add('ppl.client_id', null);
        $this->migrator->addEncrypted('ppl.client_secret', null);
        // Firemný produkt (BUSS) namiesto produktu pre súkromné osoby (PRIV).
        $this->migrator->add('ppl.business', false);
        // Štítky: A4 (4 na stranu) alebo samostatný štítok.
        $this->migrator->add('ppl.label_page', 'A4');
        // IBAN pre dobierky (prázdny = IBAN z Nastavenia → Firma).
        $this->migrator->add('ppl.cod_iban', null);
    }
};
