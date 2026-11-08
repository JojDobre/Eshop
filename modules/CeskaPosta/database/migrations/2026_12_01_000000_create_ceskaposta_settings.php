<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Kľúče z aplikácie „Podání Online“ (Nastavenia → API).
        $this->migrator->addEncrypted('ceskaposta.api_token', null);
        $this->migrator->addEncrypted('ceskaposta.secret_key', null);
        // Zákaznícke číslo (ID CČK zákazníka), PSČ podacej pošty a číslo podacieho miesta zo zmluvy.
        $this->migrator->add('ceskaposta.customer_id', null);
        $this->migrator->add('ceskaposta.post_code', null);
        $this->migrator->add('ceskaposta.location_number', null);
        $this->migrator->add('ceskaposta.contract_number', null);
        $this->migrator->add('ceskaposta.test_mode', false);
        // Formulár štítku (101 = A6 na A4, 200 = termotlačiareň Zebra).
        $this->migrator->add('ceskaposta.label_form', 101);
        // Veľkostná kategória balíka (povinná pre Balíkovnu na adresu).
        $this->migrator->add('ceskaposta.parcel_size', 'S');
    }
};
