<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Krajina účtu MyGLS (určuje adresu API: api.mygls.sk, api.mygls.cz…).
        $this->migrator->add('gls.country', 'sk');
        $this->migrator->add('gls.username', null);
        $this->migrator->addEncrypted('gls.password', null);
        $this->migrator->add('gls.client_number', null);
        $this->migrator->add('gls.test_mode', false);
        $this->migrator->add('gls.printer', 'A4_2x2');
        // Služba FDS: GLS pošle príjemcovi e-mail s možnosťou zmeniť doručenie.
        $this->migrator->add('gls.notify_recipient', true);
    }
};
