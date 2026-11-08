<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Rovnaké prihlasovacie údaje ako do portálu webship.sps-sro.sk (prístup k API zapína SPS).
        $this->migrator->add('sps.username', null);
        $this->migrator->addEncrypted('sps.password', null);
        // Služba SPS (napr. expres).
        $this->migrator->add('sps.service', 'expres');
        // Avízo príjemcovi: 0 = žiadne, 1 = e-mail, 2 = SMS, 3 = e-mail aj SMS.
        $this->migrator->add('sps.notify', 3);
        $this->migrator->add('sps.insure', true);
        // IBAN pre dobierky (prázdny = IBAN z Nastavenia → Firma, alebo účet zo zmluvy).
        $this->migrator->add('sps.cod_iban', null);
    }
};
