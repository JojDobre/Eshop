<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Prístup z Moje zásielky → Viac → Notifikácie a API → Web ePH API.
        $this->migrator->add('slovenskaposta.user_id', null);
        $this->migrator->addEncrypted('slovenskaposta.api_key', null);
        // Druh zásielky: b = balík, ek = Expres kuriér, zb = zmluvní zákazníci.
        $this->migrator->add('slovenskaposta.parcel_category', 'b');
        // Spôsob úhrady poštovného (h = v hotovosti, up = úver poštovného, fa = faktúra…).
        $this->migrator->add('slovenskaposta.payment_type', 'h');
        $this->migrator->add('slovenskaposta.contract', false);
        // IBAN pre dobierky (prázdny = IBAN z Nastavenia → Firma).
        $this->migrator->add('slovenskaposta.iban', null);
        $this->migrator->add('slovenskaposta.insure', false);
        // Hárok sa po pridaní zásielky hneď odošle pošte (potom sa už nedá upraviť).
        $this->migrator->add('slovenskaposta.auto_register', true);
    }
};
