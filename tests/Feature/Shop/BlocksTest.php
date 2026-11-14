<?php

namespace Tests\Feature\Shop;

use App\Core\Blocks\CoreBlocks;
use App\Filament\Pages\ManageBlocks;
use App\Models\BlockLayout;
use Livewire\Livewire;
use Modules\Pages\Models\Page;

class BlocksTest extends ShopTestCase
{
    public function test_home_page_is_built_from_blocks_in_each_language(): void
    {
        $coffee = $this->category('Káva');
        $this->product('Etiópia', category: $coffee);
        $sale = $this->product('Kolumbia');
        $sale->setPrice('EUR', 900, 1200);

        $this->get('/')->assertSee($this->app['translator']->get('theme::shop.latest_products'));

        $layout = new BlockLayout(['key' => BlockLayout::HOME]);
        $layout->setTranslation('blocks', 'sk', [
            ['type' => 'banner', 'data' => ['title' => 'Jesenná ponuka', 'button_label' => 'Nakupovať', 'button_url' => '/kategoria/kava']],
            ['type' => 'products', 'data' => ['title' => 'V akcii', 'source' => 'sale', 'limit' => 4]],
            ['type' => 'benefits', 'data' => ['items' => [['icon' => 'truck', 'title' => 'Doprava do 24 hodín']]]],
            ['type' => 'video', 'data' => ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']],
            ['type' => 'text', 'data' => ['title' => 'Skrytý', 'content' => '<p>x</p>', 'hidden' => true]],
            ['type' => 'unknown', 'data' => []],
        ]);
        $layout->save();

        $this->get('/')
            ->assertOk()
            ->assertSee('Jesenná ponuka')
            ->assertSee('V akcii')
            ->assertSee('Kolumbia')
            ->assertDontSee('Etiópia')
            ->assertSee('Doprava do 24 hodín')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertDontSee('Skrytý')
            ->assertDontSee($this->app['translator']->get('theme::shop.latest_products'));
    }

    public function test_pages_categories_and_theme_areas_render_blocks(): void
    {
        $page = Page::query()->where('slug->sk', 'kontakt')->firstOrFail();
        $page->setTranslation('blocks', 'sk', [['type' => 'html', 'data' => ['html' => '<div id="mapa">Mapa</div>']]])->save();
        $category = $this->category('Čaj');
        $category->setTranslation('blocks', 'sk', [['type' => 'banner', 'data' => ['title' => 'Čaje z Japonska']]])->save();
        BlockLayout::query()->create(['key' => 'area:before_footer', 'blocks' => ['sk' => [['type' => 'text', 'data' => ['content' => '<p>Novinky každý týždeň</p>']]]]]);

        $this->get($page->url())->assertSee('id="mapa"', false)->assertSee('Novinky každý týždeň');
        $this->get($category->url())->assertSee('Čaje z Japonska');
    }

    public function test_admin_edits_home_blocks(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/blocks')->assertOk();

        Livewire::test(ManageBlocks::class)
            ->set('data.blocks', ['a' => ['type' => 'banner', 'data' => ['title' => 'Vitajte', 'align' => 'center', 'height' => 'small', 'hidden' => false]]])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Vitajte', BlockLayout::blocksFor(BlockLayout::HOME)[0]['data']['title']);
        $this->get('/')->assertSee('Vitajte');

        Livewire::test(ManageBlocks::class)->set('layoutKey', 'area:before_footer')->assertSet('data.blocks', []);
        $this->get('/admin/pages/'.Page::query()->first()->id.'/edit')->assertOk();
    }

    public function test_video_embed_accepts_only_known_providers(): void
    {
        $this->assertSame('https://player.vimeo.com/video/123?dnt=1', CoreBlocks::videoEmbed('https://vimeo.com/123'));
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', CoreBlocks::videoEmbed('https://youtu.be/dQw4w9WgXcQ'));
        $this->assertNull(CoreBlocks::videoEmbed('https://evil.example/video'));
    }

    public function test_section_with_columns_and_element_blocks(): void
    {
        $page = Page::query()->create(['name' => ['sk' => 'O farme'], 'blocks' => ['sk' => [[
            'type' => 'section',
            'data' => [
                'layout' => 'wide_left', 'background' => 'dark', 'width' => 'contained', 'padding' => 'large',
                'column_1' => [
                    ['type' => 'heading', 'data' => ['title' => 'Naša farma', 'subtitle' => 'Od roku 2017', 'size' => 'large', 'align' => 'left']],
                    ['type' => 'buttons', 'data' => ['items' => [['label' => 'Obchod', 'url' => '/', 'style' => 'primary'], ['label' => 'Kontakt', 'url' => '/kontakt', 'style' => 'secondary']]]],
                ],
                'column_2' => [
                    ['type' => 'feature', 'data' => ['icon' => 'leaf', 'title' => 'Vlastný chov', 'text' => 'Bez chémie']],
                    ['type' => 'spacer', 'data' => ['height' => 'small', 'divider' => true]],
                ],
                // Tretí stĺpec pri rozložení s dvomi stĺpcami sa nezobrazí.
                'column_3' => [['type' => 'heading', 'data' => ['title' => 'Skrytý stĺpec']]],
            ],
        ]]]]);

        $this->get($page->url())->assertOk()
            ->assertSee('md:col-span-8', false)
            ->assertSee('md:col-span-4', false)
            ->assertSee('bg-zinc-900', false)
            ->assertSee('Naša farma')
            ->assertSee('Od roku 2017')
            ->assertSee('btn-secondary', false)
            ->assertSee('Vlastný chov')
            ->assertSee('id="block-section-0-c2-feature-0"', false)
            ->assertDontSee('Skrytý stĺpec');
    }

    public function test_page_and_category_display_options(): void
    {
        $page = Page::query()->create([
            'name' => ['sk' => 'Naše vajcia'],
            'content' => ['sk' => '<p>Obsah stránky</p>'],
            'display' => ['header' => 'hero', 'hero_image' => 'pages/hero.jpg', 'hero_text' => 'Čerstvé každý deň', 'breadcrumbs' => false],
        ]);

        $this->get($page->url())->assertOk()
            ->assertSee('page-hero', false)
            ->assertSee('uploads/pages/hero.jpg', false)
            ->assertSee('Čerstvé každý deň')
            ->assertSee('Obsah stránky')
            ->assertDontSee('aria-label="Breadcrumb"', false);

        $category = $this->category('Klietky');
        $category->update(['display' => ['header' => 'none', 'breadcrumbs' => true, 'show_description' => false], 'description' => ['sk' => '<p>Popis klietok</p>']]);

        $this->get($category->fresh()->url())->assertOk()
            ->assertSee('<h1 class="sr-only">Klietky</h1>', false)
            ->assertDontSee('<p>Popis klietok</p>', false);
    }
}
