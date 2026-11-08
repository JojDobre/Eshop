<?php

namespace Tests\Feature;

use App\Core\Content\ShopLocales;
use App\Core\Eshop;
use App\Core\Features\ShopSize;
use App\Core\Modules\ModuleManager;
use App\Core\Modules\ModuleStateStore;
use App\Core\Support\Currencies;
use App\Filament\Pages\Settings\ManageFeatures;
use App\Settings\CatalogSettings;
use App\Settings\CurrencySettings;
use App\Settings\GeneralSettings;
use App\Settings\TaxSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
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

    public function test_shop_size_shows_changes_applies_presets_and_detects_custom(): void
    {
        $size = app(ShopSize::class);
        $this->assertSame('small', $size->current());

        $changes = $size->changes('large');
        $this->assertContains('Viac skladov', $changes['enable']);
        $this->assertContains('Balíčky', $changes['enable']);
        $this->assertSame([], $changes['disable']);

        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/features')->assertOk()->assertSee('Veľkosť obchodu: Malý');

        Livewire::test(ManageFeatures::class)
            ->mountAction('changeSize')
            ->setActionData(['size' => 'large'])
            ->assertSee('Viac skladov')
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $catalog = app(CatalogSettings::class)->refresh();
        $this->assertTrue($catalog->feature_warehouses);
        $this->assertTrue($catalog->feature_preorders);
        $this->assertSame('large', app(ShopSize::class)->current());

        // Ručná zmena funkcie = vlastný výber.
        $catalog->feature_warehouses = false;
        $catalog->save();
        $this->assertSame('custom', app(ShopSize::class)->current());

        // Návrat na malý obchod vypne sklady, voliteľné funkcie (napr. balíčky) ostanú.
        $this->assertSame([], app(ShopSize::class)->apply('small'));
        $catalog->refresh();
        $this->assertFalse($catalog->feature_warehouses);
        $this->assertTrue($catalog->feature_bundles);
        $this->assertSame('small', app(ShopSize::class)->current());
    }

    public function test_enabling_a_module_runs_its_migrations(): void
    {
        // V MySQL/MariaDB vytvorenie tabuľky ukončí transakciu testu, preto sa test spúšťa iba na SQLite.
        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->markTestSkipped('DDL v transakcii testu podporuje iba SQLite.');
        }

        $path = $this->tempPath.'/modules';
        File::ensureDirectoryExists($path.'/Sample/database/migrations');
        file_put_contents($path.'/Sample/module.json', json_encode(['name' => 'sample', 'version' => '1.0.0', 'core' => false, 'enabled' => false]));
        file_put_contents($path.'/Sample/database/migrations/2030_01_01_000000_create_sample_items.php', <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sample_items', fn (Blueprint $table) => $table->id());
    }
};
PHP);

        $manager = new ModuleManager([$path], new ModuleStateStore($this->tempPath.'/sample-state.json'), Eshop::VERSION);
        $this->assertFalse(Schema::hasTable('sample_items'));

        $manager->enable('sample');

        $this->assertTrue(Schema::hasTable('sample_items'));
    }
}
