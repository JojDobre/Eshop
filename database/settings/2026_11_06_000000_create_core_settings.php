<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.shop_name', 'Môj e-shop');
        $this->migrator->add('general.tagline', null);
        $this->migrator->add('general.contact_email', 'info@example.com');
        $this->migrator->add('general.contact_phone', null);
        $this->migrator->add('general.default_locale', 'sk');
        $this->migrator->add('general.default_currency', 'EUR');
        $this->migrator->add('general.country', 'SK');
        $this->migrator->add('general.theme', 'default');

        $this->migrator->add('branding.logo', null);
        $this->migrator->add('branding.logo_dark', null);
        $this->migrator->add('branding.favicon', null);
        $this->migrator->add('branding.primary_color', '#4f46e5');

        $this->migrator->add('company.company_name', null);
        $this->migrator->add('company.street', null);
        $this->migrator->add('company.city', null);
        $this->migrator->add('company.zip', null);
        $this->migrator->add('company.country', 'SK');
        $this->migrator->add('company.company_id', null);
        $this->migrator->add('company.tax_id', null);
        $this->migrator->add('company.vat_id', null);
        $this->migrator->add('company.iban', null);
        $this->migrator->add('company.swift', null);
        $this->migrator->add('company.registration', null);
    }
};
