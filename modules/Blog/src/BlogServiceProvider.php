<?php

namespace Modules\Blog;

use App\Core\Blocks\BlockType;
use App\Core\Blocks\BlockTypes;
use App\Core\Blocks\CoreBlocks;
use App\Core\Content\TranslatableModels;
use App\Core\Menus\MenuLinkType;
use App\Core\Menus\MenuLinkTypes;
use App\Core\Modules\ModuleServiceProvider;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Facades\View;
use Modules\Blog\Filament\ProductPostsRelationManager;
use Modules\Blog\Models\Post;
use Modules\Blog\Models\PostCategory;
use Modules\Catalog\Filament\ProductRelations;
use Modules\Storefront\Sitemap\SitemapRegistry;
use Modules\Storefront\Sitemap\SitemapUrl;

class BlogServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'blog';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(TranslatableModels::class)->register(Post::class, fn () => __('blog::admin.posts'));

        $this->app->make(SitemapRegistry::class)->add(function (): iterable {
            yield new SitemapUrl(route('blog.index'));

            foreach (Post::query()->published()->get() as $post) {
                yield new SitemapUrl($post->url(), $post->updated_at);
            }
        });

        // Súvisiace články pri produkte a v kategórii (prepojenie nastavené z článku alebo z produktu).
        View::composer('theme::templates.product', function (\Illuminate\View\View $view): void {
            $product = $view->getData()['product'] ?? null;
            $view->with('relatedPosts', $product ? Post::query()->published()->whereHas('products', fn ($query) => $query->whereKey($product->id))->latest('published_at')->limit(3)->get() : collect());
        });
        View::composer('theme::templates.category', function (\Illuminate\View\View $view): void {
            $category = $view->getData()['category'] ?? null;
            $view->with('relatedPosts', $category ? Post::query()->published()->whereHas('catalogCategories', fn ($query) => $query->whereKey($category->id))->latest('published_at')->limit(3)->get() : collect());
        });

        ProductRelations::add(ProductPostsRelationManager::class);

        $this->registerBlocks();
        $this->registerMenuLinks();
    }

    /**
     * Blok „Články“: najnovšie, z kategórie alebo vybrané (na úvodnú stránku, do kategórií, k produktom).
     */
    private function registerBlocks(): void
    {
        $this->app->make(BlockTypes::class)->register(new BlockType('blog_posts', fn () => __('blog::admin.block.label'), fn () => [
            TextInput::make('title')->label(__('admin.blocks.title'))->maxLength(160),
            Select::make('source')->label(__('blog::admin.block.source'))
                ->options(['latest' => __('blog::admin.block.latest'), 'category' => __('blog::admin.block.category'), 'selected' => __('blog::admin.block.selected')])
                ->default('latest')->selectablePlaceholder(false)->live(),
            Select::make('blog_category_id')->label(__('blog::admin.category'))
                ->options(fn () => PostCategory::query()->orderBy('position')->get()->mapWithKeys(fn ($category) => [$category->id => $category->name])->all())
                ->visible(fn (Get $get) => $get('source') === 'category')->required(fn (Get $get) => $get('source') === 'category'),
            Select::make('post_ids')->label(__('blog::admin.posts'))->multiple()->searchable()
                ->options(fn () => Post::query()->latest('id')->limit(500)->get()->mapWithKeys(fn ($post) => [$post->id => $post->name])->all())
                ->visible(fn (Get $get) => $get('source') === 'selected')->required(fn (Get $get) => $get('source') === 'selected'),
            TextInput::make('limit')->label(__('blog::admin.block.limit'))->integer()->minValue(1)->maxValue(12)->default(3)
                ->visible(fn (Get $get) => $get('source') !== 'selected'),
            CoreBlocks::hidden(),
        ], prepare: function (array $data): array {
            $query = Post::query()->published();
            $limit = max(1, min(12, (int) ($data['limit'] ?? 3)));

            $posts = match ($data['source'] ?? 'latest') {
                'category' => $query->where('blog_category_id', (int) ($data['blog_category_id'] ?? 0))->latest('published_at')->limit($limit)->get(),
                'selected' => ($ids = array_map('intval', (array) ($data['post_ids'] ?? []))) === [] ? collect()
                    : $query->whereIn('id', $ids)->get()->sortBy(fn (Post $post) => array_search($post->id, $ids, true))->values(),
                default => $query->latest('published_at')->limit($limit)->get(),
            };

            return [...$data, 'posts' => $posts];
        }, icon: 'heroicon-o-newspaper'));
    }

    private function registerMenuLinks(): void
    {
        $links = $this->app->make(MenuLinkTypes::class);
        $links->register(new MenuLinkType(
            key: 'blog_category',
            label: fn () => __('blog::admin.category'),
            options: fn () => ['0' => __('blog::admin.all_posts'), ...PostCategory::query()->orderBy('position')->get()->mapWithKeys(fn ($category) => [(string) $category->id => $category->name])->all()],
            resolve: function (string $target): ?array {
                if ($target === '0') {
                    return ['url' => route('blog.index'), 'label' => __('theme::blog.title')];
                }

                $category = PostCategory::query()->find((int) $target);

                return $category ? ['url' => $category->url(), 'label' => $category->name] : null;
            },
        ));
        $links->register(new MenuLinkType(
            key: 'blog_post',
            label: fn () => __('blog::admin.post'),
            options: fn () => Post::query()->latest('id')->limit(500)->get()->mapWithKeys(fn ($post) => [(string) $post->id => $post->name])->all(),
            resolve: function (string $target): ?array {
                $post = Post::query()->published()->find((int) $target);

                return $post ? ['url' => $post->url(), 'label' => $post->name] : null;
            },
        ));
    }
}
