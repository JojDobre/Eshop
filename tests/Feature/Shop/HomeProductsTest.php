<?php

namespace Tests\Feature\Shop;

use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Modules\Catalog\CatalogBlocks;

class HomeProductsTest extends ShopTestCase
{
    private function activate(string $name): void
    {
        $settings = app(GeneralSettings::class);
        $settings->theme = $name;
        $settings->save();

        $themes = app(ThemeManager::class);
        $themes->flush();
        View::replaceNamespace('theme', $themes->viewPaths());
        View::getFinder()->flush();
        app('translator')->setLoaded([]);
        $this->app->forgetScopedInstances();
    }

    public function test_home_page_can_show_best_sellers_instead_of_new_arrivals(): void
    {
        Mail::fake();
        $old = $this->product('Japonská nosná', '0.40', 500);
        $this->product('Novinka mesiaca', '1.00', 500);

        $this->post(route('cart.add'), ['product_id' => $old->id, 'quantity' => 20]);
        $this->post(route('checkout.store'), $this->checkoutData());

        // Predvolene novinky: najnovší produkt je prvý.
        $this->assertSame('Novinka mesiaca', CatalogBlocks::homeProducts('latest')->first()->name);
        $this->get('/')->assertOk()->assertSee(__('theme::shop.home_products.latest'));

        // Najpredávanejšie: predávaný produkt je prvý, ostatné doplnia novinky.
        $bestsellers = CatalogBlocks::homeProducts('bestsellers');
        $this->assertSame(['Japonská nosná', 'Novinka mesiaca'], $bestsellers->pluck('name')->all());

        foreach (['default', 'farma'] as $theme) {
            $this->activate($theme);
            app(ThemeOptions::class)->save(app(ThemeManager::class)->find($theme), ['products_source' => 'bestsellers']);

            $this->get('/')->assertOk()
                ->assertSee(__('theme::shop.home_products.bestsellers'))
                ->assertSee($theme === 'farma' ? 'farma-h2' : 'home-latest', false)
                ->assertSeeInOrder(['Japonská nosná', 'Novinka mesiaca']);
        }
    }
}
