<?php

namespace Tests\Feature\Shop;

use App\Core\Themes\ThemeChecker;
use App\Core\Themes\ThemeManager;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\View;
use Modules\Pages\Models\Page;

class BundledThemesTest extends ShopTestCase
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

    public function test_bundled_themes_pass_the_check(): void
    {
        foreach (['default', 'fresh', 'starter'] as $name) {
            $theme = app(ThemeManager::class)->find($name);
            $this->assertNotNull($theme, $name);
            $errors = collect(app(ThemeChecker::class)->check($theme))->where('level', 'error');
            $this->assertSame([], $errors->values()->all(), $name);
        }
    }

    public function test_fresh_theme_renders_its_own_layout(): void
    {
        $coffee = $this->category('Káva');
        $this->product('Etiópia', category: $coffee);
        $this->activate('fresh');

        $this->get('/')
            ->assertOk()
            ->assertSee('--fresh-accent: #166534', false)
            ->assertSee('Rýchle doručenie')
            ->assertSee('Etiópia');
        $this->get($coffee->url())->assertOk()->assertSee('Etiópia');
        $this->get(route('cart.show'))->assertOk();
    }

    public function test_starter_theme_loads_plain_stylesheet_and_page_template(): void
    {
        $this->activate('starter');
        $page = Page::query()->create(['name' => 'O nás', 'slug' => 'o-nas', 'content' => '<p>Sme malý obchod.</p>']);

        $this->get('/')->assertOk()->assertSee(theme_asset('custom.css', 'starter'), false);
        $this->get($page->url())->assertOk()->assertSee('Sme malý obchod.');
        $this->get(theme_asset('custom.css', 'starter'))->assertOk();
    }
}
