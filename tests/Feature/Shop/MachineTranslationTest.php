<?php

namespace Tests\Feature\Shop;

use App\Core\Content\MachineTranslator;
use App\Core\Content\ShopLocales;
use App\Settings\GeneralSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Models\Product;
use Modules\Translator\Filament\Pages\BulkTranslation;
use Modules\Translator\Filament\Pages\ManageTranslatorSettings;
use Modules\Translator\MachineTranslationService;
use Modules\Translator\Providers\GoogleProvider;
use Modules\Translator\TranslationLimitExceeded;
use Modules\Translator\TranslationQueue;
use Modules\Translator\TranslatorSettings;

class MachineTranslationTest extends ShopTestCase
{
    /** @var list<array<string, mixed>> */
    private array $requests = [];

    protected function setUp(): void
    {
        parent::setUp();

        $general = app(GeneralSettings::class);
        $general->locales = ['en'];
        $general->save();
        app(ShopLocales::class)->refresh();

        $settings = app(TranslatorSettings::class);
        $settings->fill(['provider' => 'deepl', 'api_key' => 'test-key:fx', 'monthly_limit' => 1000, 'glossary' => [
            ['term' => 'Kaviareň u Jožka', 'translations' => []],
            ['term' => 'zrnková', 'translations' => ['en' => 'whole bean']],
        ]])->save();

        // Napodobnené DeepL: „preloží“ text pridaním predpony a zachová chránené pojmy.
        Http::fake(['api-free.deepl.com/*' => function (Request $request) {
            $this->requests[] = $request->data();

            return Http::response(['translations' => array_map(fn (string $text) => ['text' => 'EN '.$text], $request->data()['text'])]);
        }]);
    }

    public function test_model_is_translated_with_glossary_and_marked_for_review(): void
    {
        $product = $this->product('Etiópia zrnková', attributes: ['description' => '<p>Káva z dielne Kaviareň u Jožka &amp; spol.</p>']);
        $translator = app(MachineTranslator::class);

        $characters = $translator->translateModel($product, 'sk', 'en');

        $this->assertSame(['Etiópia <x-keep>whole bean</x-keep>', '<p>Káva z dielne <x-keep>Kaviareň u Jožka</x-keep> &amp; spol.</p>'], $this->requests[0]['text']);
        $this->assertSame('EN-GB', $this->requests[0]['target_lang']);

        $product->refresh();
        $this->assertSame('EN Etiópia whole bean', $product->getTranslation('name', 'en'));
        $this->assertSame('en-etiopia-whole-bean', $product->getTranslation('slug', 'en'));
        $this->assertStringContainsString('Kaviareň u Jožka', $product->getTranslation('description', 'en'));
        $this->assertGreaterThan(0, $characters);
        $this->assertTrue($translator->needsReview($product, 'en'));
        $this->assertSame($characters, app(TranslatorSettings::class)->used_characters);
    }

    public function test_monthly_limit_is_enforced(): void
    {
        $settings = app(TranslatorSettings::class);
        $settings->monthly_limit = 5;
        $settings->save();

        $this->expectException(TranslationLimitExceeded::class);
        app(MachineTranslator::class)->translateModel($this->product('Etiópia zrnková'), 'sk', 'en');
    }

    public function test_google_provider(): void
    {
        Http::fake([GoogleProvider::URL.'*' => Http::response(['data' => ['translations' => [['translatedText' => 'Ethiopia &amp; Kenya']]]])]);
        $service = app(MachineTranslationService::class);
        $service->useProvider(new GoogleProvider('g-key'));

        $this->assertSame(['Ethiopia & Kenya'], $service->translate(['Etiópia a Keňa'], 'sk', 'en'));
    }

    public function test_admin_translates_reviews_and_approves(): void
    {
        $product = $this->product('Mlynček');
        $this->actingAs($this->superAdmin());

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->assertActionHidden('machineTranslate')
            ->callAction('editLocale_en')
            ->assertActionVisible('machineTranslate')
            ->callAction('machineTranslate')
            ->assertFormSet(['name' => 'EN Mlynček'])
            ->assertActionVisible('approveTranslation')
            ->fillForm(['name' => 'Hand grinder'])
            ->call('save')
            ->assertActionHidden('approveTranslation');

        $this->assertFalse(app(MachineTranslator::class)->needsReview($product, 'en'));
        $this->assertSame('Hand grinder', $product->fresh()->getTranslation('name', 'en'));
    }

    public function test_bulk_translation_queue(): void
    {
        $this->product('Etiópia');
        $this->product('Keňa');
        $translated = $this->product('Brazília');
        $translated->setTranslation('name', 'en', 'Brazil')->save();
        $queue = app(TranslationQueue::class);

        $estimate = $queue->estimate([Product::class], 'en');
        $this->assertSame(2, $estimate['records']);

        $this->actingAs($this->superAdmin());
        $this->get('/admin/translations')->assertOk();

        Livewire::test(BulkTranslation::class)
            ->fillForm(['locale' => 'en', 'models' => [Product::class]])
            ->callAction('estimate')
            ->assertSet('estimate.records', 2)
            ->callAction('start')
            ->assertNotified();
        $this->assertSame(2, $queue->pending());

        Livewire::test(BulkTranslation::class)->call('processBatch');
        $this->assertSame(0, $queue->pending());
        $this->assertSame('EN Keňa', Product::query()->get()->firstWhere(fn ($p) => $p->getTranslation('name', 'sk') === 'Keňa')->getTranslation('name', 'en'));
        $this->assertSame('Brazil', $translated->fresh()->getTranslation('name', 'en'));
    }

    public function test_settings_page_and_test_action(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/translator')->assertOk();

        Livewire::test(ManageTranslatorSettings::class)
            ->callAction('test')
            ->assertNotified();
    }
}
