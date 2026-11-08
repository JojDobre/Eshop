<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Newsletter: prihlásenie v päte a v pokladni, vždy s potvrdením e-mailom (double opt-in).
        $this->migrator->add('marketing.newsletter_enabled', false);
        $this->migrator->add('marketing.newsletter_checkout', true);
        // Synchronizácia odberateľov: none, mailchimp, ecomail.
        $this->migrator->add('marketing.newsletter_provider', 'none');
        $this->migrator->addEncrypted('marketing.mailchimp_api_key', null);
        $this->migrator->add('marketing.mailchimp_list_id', null);
        $this->migrator->addEncrypted('marketing.ecomail_api_key', null);
        $this->migrator->add('marketing.ecomail_list_id', null);

        // Opustené košíky: jedna pripomienka po nastavenom počte hodín.
        $this->migrator->add('marketing.abandoned_enabled', false);
        $this->migrator->add('marketing.abandoned_delay_hours', 3);

        // XML feedy (Heureka, Google Merchant, Facebook katalóg).
        $this->migrator->add('marketing.feeds_enabled', false);
        $this->migrator->add('marketing.feeds_out_of_stock', true);

        // Meracie kódy – vložia sa až po súhlase s cookies (analytika / marketing).
        $this->migrator->add('marketing.ga4_id', null);
        $this->migrator->add('marketing.google_ads_id', null);
        $this->migrator->add('marketing.google_ads_label', null);
        $this->migrator->add('marketing.meta_pixel_id', null);
    }
};
