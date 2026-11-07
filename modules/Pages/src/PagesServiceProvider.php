<?php

namespace Modules\Pages;

use App\Core\Events\EshopInstalled;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Pages\Listeners\CreateDefaultPages;
use Modules\Pages\Models\Page;
use Modules\Storefront\Pages\LegalPages;
use Modules\Storefront\Sitemap\SitemapRegistry;
use Modules\Storefront\Sitemap\SitemapUrl;

class PagesServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'pages';
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(EshopInstalled::class, CreateDefaultPages::class);

        $legal = $this->app->make(LegalPages::class);
        $legal->register(LegalPages::TERMS, fn () => $this->urlForRole(Page::ROLE_TERMS));
        $legal->register(LegalPages::PRIVACY, fn () => $this->urlForRole(Page::ROLE_PRIVACY));

        View::composer('theme::partials.footer', function (\Illuminate\View\View $view): void {
            $view->with('footerPages', Page::query()->visible()->where('show_in_footer', true)->get());
        });

        $this->app->make(SitemapRegistry::class)->add(function (): iterable {
            foreach (Page::query()->visible()->get() as $page) {
                yield new SitemapUrl($page->url(), $page->updated_at);
            }
        });
    }

    private function urlForRole(string $role): ?string
    {
        return Page::query()->where('role', $role)->where('is_visible', true)->first()?->url();
    }
}
