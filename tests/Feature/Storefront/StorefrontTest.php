<?php

namespace Tests\Feature\Storefront;

use App\Settings\BrandingSettings;
use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Tests\Feature\InstalledTestCase;

class StorefrontTest extends InstalledTestCase
{
    public function test_home_page_shows_shop_details_in_shop_language(): void
    {
        $general = app(GeneralSettings::class);
        $general->shop_name = 'Kaviareň u Jožka';
        $general->tagline = 'Výberová káva';
        $general->save();

        $company = app(CompanySettings::class);
        $company->company_name = 'Jožko s.r.o.';
        $company->company_id = '12345678';
        $company->save();

        $this->get('/')
            ->assertOk()
            ->assertSee('Kaviareň u Jožka')
            ->assertSee('Výberová káva')
            ->assertSee('Jožko s.r.o.')
            ->assertSee('IČO: 12345678')
            ->assertSee('Obchod pripravujeme', false);
    }

    public function test_home_page_is_translated_to_the_shop_language(): void
    {
        $general = app(GeneralSettings::class);
        $general->default_locale = 'en';
        $general->save();

        $this->get('/')->assertSee('Our shop is coming soon')->assertSee('lang="en"', false);
    }

    public function test_brand_color_is_applied_with_readable_text_color(): void
    {
        $branding = app(BrandingSettings::class);
        $branding->primary_color = '#FACC15'; // svetlá žltá → tmavý text
        $branding->save();

        $this->get('/')
            ->assertSee('--brand-primary: #facc15', false)
            ->assertSee('--brand-primary-foreground: #18181b', false);
    }

    public function test_installer_is_not_available_after_installation(): void
    {
        $this->get('/install')->assertNotFound();
    }
}
