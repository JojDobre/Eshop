<?php

namespace Tests\Feature;

use App\Core\Content\ShopLocales;
use App\Core\Modules\ModuleManager;
use App\Core\Support\Currencies;
use App\Filament\Pages\Settings\ManageFeatures;
use App\Settings\CurrencySettings;
use App\Settings\GeneralSettings;
use App\Settings\TaxSettings;
use Livewire\Livewire;

class FeaturesTest extends InstalledTestCase
{
    public function test_features_can_be_switched_without_losing_configuration(): void
    {
        $general = app(GeneralSettings::class);
        $general->locales = ['en'];
        $general->save();
        $currencies = app(CurrencySettings::class);
        $currencies->fill(['enabled' => ['CZK'], 'rates' => ['CZK' => '25']])->save();
        app(ShopLocales::class)->refresh();
        app(Currencies::class)->refresh();

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/features')->assertOk()->assertSee('Viac jazykov')->assertSee('Režim OSS');

        Livewire::test(ManageFeatures::class)
            ->assertSet('data.features.core__locales', true)
            ->set('data.features.core__locales', false)
            ->set('data.features.core__currencies', false)
            ->set('data.features.tax__oss_enabled', true)
            ->call('save')
            ->assertNotified();

        app(ShopLocales::class)->refresh();
        app(Currencies::class)->refresh();
        $this->assertSame(['sk'], app(ShopLocales::class)->enabled());
        $this->assertSame(['EUR'], app(Currencies::class)->enabled());
        $this->assertSame(['en'], app(GeneralSettings::class)->locales);
        $this->assertSame(['CZK'], app(CurrencySettings::class)->enabled);
        $this->assertTrue(app(TaxSettings::class)->oss_enabled);

        // Opätovné zapnutie obnoví pôvodné jazyky a meny.
        Livewire::test(ManageFeatures::class)
            ->set('data.features.core__locales', true)
            ->set('data.features.core__currencies', true)
            ->call('save');
        app(ShopLocales::class)->refresh();
        $this->assertSame(['sk', 'en'], app(ShopLocales::class)->enabled());
    }

    public function test_locales_cannot_be_enabled_without_choosing_them(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageFeatures::class)
            ->set('data.features.core__locales', true)
            ->call('save')
            ->assertNotified(__('admin.features.error'));

        $this->assertSame(['sk'], app(ShopLocales::class)->enabled());
    }

    public function test_optional_modules_can_be_switched(): void
    {
        $this->actingAs($this->superAdmin());
        $this->assertTrue(app(ModuleManager::class)->isEnabled('returns'));

        Livewire::test(ManageFeatures::class)
            ->set('data.modules.returns', false)
            ->call('save')
            ->assertRedirect();

        $this->assertFalse(app(ModuleManager::class)->isEnabled('returns'));
    }
}
