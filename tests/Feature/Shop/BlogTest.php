<?php

namespace Tests\Feature\Shop;

use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Models\BlockLayout;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Modules\Blog\BlogSettings;
use Modules\Blog\Filament\Resources\Posts\Pages\CreatePost;
use Modules\Blog\Filament\Resources\Posts\Pages\EditPost;
use Modules\Blog\Models\Post;
use Modules\Blog\Models\PostCategory;
use Modules\Blog\Models\PostTag;

class BlogTest extends ShopTestCase
{
    private function article(array $attributes = []): Post
    {
        return Post::query()->create([
            'name' => 'Ako vybrať kávu',
            'excerpt' => 'Sprievodca pre začiatočníkov.',
            'status' => Post::PUBLISHED,
            'published_at' => now()->subDay(),
            'blocks' => [
                ['type' => 'text', 'data' => ['content' => '<h2>Pražba</h2><p>'.str_repeat('slovo ', 450).'</p><h3>Svetlá pražba</h3><p>Text.</p><h2>Mletie</h2>']],
                ['type' => 'quote', 'data' => ['text' => 'Káva je život.', 'author' => 'Barista']],
            ],
            ...$attributes,
        ]);
    }

    private function formToken(int $secondsAgo = 10): string
    {
        return Crypt::encryptString((string) (time() - $secondsAgo));
    }

    public function test_published_post_page_with_toc_reading_time_and_products(): void
    {
        $coffee = $this->product('Etiópia');
        $category = PostCategory::query()->create(['name' => 'Návody']);
        $post = $this->article(['blog_category_id' => $category->id]);
        $post->products()->attach($coffee);
        $post->tags()->attach(PostTag::query()->create(['name' => 'Espresso']));

        $this->get($post->url())
            ->assertOk()
            ->assertSee('Obsah článku')
            ->assertSee('href="#prazba"', false)
            ->assertSee('id="svetla-prazba"', false)
            ->assertSee('3 minúty čítania')
            ->assertSee('Káva je život.')
            ->assertSee('Etiópia')
            ->assertSee('#Espresso');

        $this->get(route('blog.index'))->assertOk()->assertSee('Ako vybrať kávu');
        $this->get($category->url())->assertOk()->assertSee('Ako vybrať kávu');
        $this->get(route('blog.tag', 'espresso'))->assertOk()->assertSee('Ako vybrať kávu');
        $this->get(route('blog.rss'))->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8')->assertSee($post->url(), false);

        // Prepojenie oboma smermi: článok pri produkte.
        $this->get($coffee->url())->assertSee('Súvisiace články')->assertSee('Ako vybrať kávu');
    }

    public function test_drafts_and_scheduled_posts_are_hidden_but_previewable(): void
    {
        $draft = $this->article(['name' => 'Koncept článku', 'status' => Post::DRAFT, 'published_at' => null]);
        $scheduled = $this->article(['name' => 'Budúci článok', 'published_at' => now()->addDay()]);

        $this->get($draft->url())->assertNotFound();
        $this->get($scheduled->url())->assertNotFound();
        $this->get(route('blog.index'))->assertDontSee('Koncept článku')->assertDontSee('Budúci článok');
        $this->get($scheduled->previewUrl())->assertOk()->assertSee('Náhľad článku')->assertSee('noindex', false);

        $this->travel(2)->days();
        $this->get($scheduled->url())->assertOk();
    }

    public function test_comments_are_moderated_and_protected_from_spam(): void
    {
        $post = $this->article();

        $this->post(route('blog.comment', $post->slug), ['name' => 'Jana', 'email' => 'jana@example.com', 'content' => 'Skvelý článok!', 'form_token' => $this->formToken()])
            ->assertRedirect()->assertSessionHas('comment_status');
        $this->assertDatabaseHas('blog_comments', ['name' => 'Jana', 'is_approved' => false]);
        $this->get($post->url())->assertDontSee('Skvelý článok!');

        // Robot: vyplnené skryté pole alebo príliš rýchle odoslanie sa neuloží.
        $this->post(route('blog.comment', $post->slug), ['name' => 'Bot', 'email' => 'bot@example.com', 'content' => 'Spam spam', 'website' => 'http://spam', 'form_token' => $this->formToken()]);
        $this->post(route('blog.comment', $post->slug), ['name' => 'Bot2', 'email' => 'bot@example.com', 'content' => 'Spam spam', 'form_token' => $this->formToken(0)]);
        $this->assertDatabaseMissing('blog_comments', ['name' => 'Bot']);
        $this->assertDatabaseMissing('blog_comments', ['name' => 'Bot2']);

        $settings = app(BlogSettings::class);
        $settings->comments_require_approval = false;
        $settings->save();
        $this->post(route('blog.comment', $post->slug), ['name' => 'Peter', 'email' => 'peter@example.com', 'content' => 'Hneď viditeľný', 'form_token' => $this->formToken()]);
        $this->get($post->url())->assertSee('Hneď viditeľný');

        $post->update(['comments_enabled' => false]);
        $this->post(route('blog.comment', $post->slug), ['name' => 'X', 'email' => 'x@example.com', 'content' => 'Zatvorené', 'form_token' => $this->formToken()])->assertNotFound();
    }

    public function test_admin_creates_post_with_products_and_restores_versions(): void
    {
        $coffee = $this->product('Kolumbia');
        $this->actingAs($admin = $this->superAdmin());

        $this->get('/admin/blog/posts')->assertOk();
        $this->get('/admin/blog/categories')->assertOk();
        $this->get('/admin/blog/comments')->assertOk();
        $this->get('/admin/settings/blog')->assertOk();

        Livewire::test(CreatePost::class)
            ->fillForm(['name' => 'Recept na cold brew', 'status' => Post::PUBLISHED, 'products' => [$coffee->id], 'author_id' => $admin->id])
            ->set('data.blocks', ['a' => ['type' => 'text', 'data' => ['content' => '<p>Prvá verzia</p>', 'hidden' => false]]])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::query()->where('slug->sk', 'recept-na-cold-brew')->sole();
        $this->assertTrue($post->isPublished());
        $this->assertTrue($post->products->contains($coffee));

        $post->update(['blocks' => [['type' => 'text', 'data' => ['content' => '<p>Druhá verzia</p>']]]]);
        $this->assertSame(2, $post->versions()->count());

        $first = $post->versions()->where('version', 1)->sole();
        Livewire::test(EditPost::class, ['record' => $post->id])->callAction('versions', ['version' => $first->id]);
        $this->assertStringContainsString('Prvá verzia', $post->fresh()->blocks[0]['data']['content']);

        // Súvisiace články pri produkte v administrácii.
        $this->get('/admin/products/'.$coffee->id.'/edit')->assertOk()->assertSee('Súvisiace články');
    }

    public function test_latest_posts_block_on_home_page(): void
    {
        $this->article(['name' => 'Novinka z blogu']);
        BlockLayout::query()->create(['key' => BlockLayout::HOME, 'blocks' => ['sk' => [['type' => 'blog_posts', 'data' => ['title' => 'Z nášho blogu', 'source' => 'latest', 'limit' => 3]]]]]);

        $this->get('/')->assertSee('Z nášho blogu')->assertSee('Novinka z blogu');
    }

    public function test_post_without_blocks_renders(): void
    {
        $post = $this->article(['name' => 'Iba perex', 'blocks' => null]);

        $this->get(route('blog.index'))->assertOk()->assertSee('Iba perex');
        $this->get($post->url())->assertOk();
    }

    public function test_blog_layout_is_configurable_in_theme_settings(): void
    {
        $category = PostCategory::query()->create(['name' => 'Návody']);
        $first = $this->article(['name' => 'Starší článok', 'published_at' => now()->subDays(3), 'blog_category_id' => $category->id]);
        $newest = $this->article(['name' => 'Najnovší článok', 'cover_image' => 'blog/cover.jpg']);

        $theme = app(ThemeManager::class)->active();
        app(ThemeOptions::class)->save($theme, [
            'blog_index_sections' => [['section' => 'featured'], ['section' => 'posts'], ['section' => 'header']],
            'blog_list_layout' => 'list',
            'blog_sidebar' => 'right',
            'blog_show_excerpt' => false,
            'blog_post_layout' => 'hero',
            'blog_post_width' => 'wide',
            'blog_post_sections' => [['section' => 'related'], ['section' => 'content']],
        ]);

        $html = $this->get(route('blog.index'))->assertOk()
            ->assertSee(__('theme::blog.featured'))
            ->assertSee('blog-sidebar', false)
            ->assertSee('sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]', false)
            ->assertDontSee('Sprievodca pre začiatočníkov.')
            ->getContent();
        // Zvýraznený článok je hore, v zozname sa už neopakuje.
        $this->assertSame(1, substr_count($html, '>Najnovší článok</a>'));
        $this->assertLessThan(strpos($html, '>Starší článok</a>'), strpos($html, '>Najnovší článok</a>'));

        $postHtml = $this->get($newest->url())->assertOk()
            ->assertSee('uploads/blog/cover.jpg', false)
            ->assertSee('blog-post-wide', false)
            ->getContent();
        // Súvisiace články pred textom článku (podľa poradia sekcií).
        $this->assertLessThan(strpos($postHtml, 'blog-post-content'), strpos($postHtml, 'related-posts'));
    }
}
