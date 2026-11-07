<?php

namespace Modules\Catalog;

use App\Core\Events\EshopInstalled;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
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

        Event::listen(EshopInstalled::class, SeedDemoCatalog::class);

        // Kategórie v hlavičke obchodu.
        View::composer('theme::partials.header', function (\Illuminate\View\View $view): void {
            $view->with('navigationCategories', Category::query()->visible()->roots()->limit(8)->get());
        });

        // Úvodná stránka: kategórie a najnovšie produkty.
        View::composer('theme::templates.home', function (\Illuminate\View\View $view): void {
            $view->with([
                'homeCategories' => Category::query()->visible()->roots()->limit(6)->get(),
                'latestProducts' => Product::query()->visible()->with('prices')->latest()->limit(8)->get(),
            ]);
        });

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
