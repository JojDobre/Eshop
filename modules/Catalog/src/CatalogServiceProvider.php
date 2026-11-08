<?php

namespace Modules\Catalog;

use App\Core\Content\TranslatableModels;
use App\Core\Events\EshopInstalled;
use App\Core\Features\FeatureToggles;
use App\Core\Menus\MenuLinkType;
use App\Core\Menus\MenuLinkTypes;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Catalog\Events\StockLow;
use Modules\Catalog\Listeners\NotifyAboutLowStock;
use Modules\Catalog\Listeners\SeedDemoCatalog;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Storefront\Sitemap\SitemapRegistry;
use Modules\Storefront\Sitemap\SitemapUrl;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'catalog';
    }

    public function boot(): void
    {
        parent::boot();

        CatalogFeatures::register($this->app->make(FeatureToggles::class));

        // Kategórie a produkty ako cieľ položky ponuky (menu).
        $links = $this->app->make(MenuLinkTypes::class);
        $links->register(new MenuLinkType(
            key: 'category',
            label: fn () => __('catalog::admin.category.label'),
            options: fn () => Category::query()->with('parent.parent')->get()->mapWithKeys(fn (Category $category) => [(string) $category->id => $category->pathName()])->sort()->all(),
            resolve: function (string $target): ?array {
                $category = Category::query()->where('is_visible', true)->find((int) $target);

                return $category ? ['url' => $category->url(), 'label' => $category->name] : null;
            },
        ));
        $links->register(new MenuLinkType(
            key: 'product',
            label: fn () => __('catalog::admin.product.label'),
            options: fn () => Product::query()->orderBy('id')->limit(500)->get()->mapWithKeys(fn (Product $product) => [(string) $product->id => $product->name])->all(),
            resolve: function (string $target): ?array {
                $product = Product::query()->visible()->find((int) $target);

                return $product ? ['url' => $product->url(), 'label' => $product->name] : null;
            },
        ));

        // Preložiteľný obsah (hromadný preklad).
        $this->app->make(TranslatableModels::class)->register(Product::class, fn () => __('catalog::admin.product.plural'));
        $this->app->make(TranslatableModels::class)->register(Category::class, fn () => __('catalog::admin.category.plural'));

        Event::listen(EshopInstalled::class, SeedDemoCatalog::class);
        Event::listen(StockLow::class, NotifyAboutLowStock::class);

        // Kategórie v hlavičke obchodu.
        View::composer('theme::partials.header', function (\Illuminate\View\View $view): void {
            $view->with('navigationCategories', Category::query()->visible()->roots()->limit(8)->get());
        });

        // Úvodná stránka: kategórie a najnovšie produkty.
        View::composer('theme::templates.home', function (\Illuminate\View\View $view): void {
            $view->with([
                'homeCategories' => Category::query()->visible()->roots()->limit(6)->get(),
                'latestProducts' => Product::query()->visible()->with(['prices', 'taxRate', 'variants' => fn ($variants) => $variants->where('is_active', true)->with('prices')])->latest()->limit(8)->get(),
            ]);
        });

        $this->app->singleton(StockService::class);
        $this->app->scoped(StockLots::class);

        $this->app->make(SitemapRegistry::class)->add(function (): iterable {
            foreach (Category::query()->visible()->lazyById(200) as $category) {
                yield new SitemapUrl($category->url(), $category->updated_at);
            }

            foreach (Product::query()->visible()->lazyById(200) as $product) {
                yield new SitemapUrl($product->url(), $product->updated_at);
            }
        });
    }
}
