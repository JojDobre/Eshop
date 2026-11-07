<?php

namespace Modules\Storefront;

use App\Core\Content\AlternateUrls;
use App\Core\Content\LocalizedUrls;
use App\Core\Content\ShopLocales;
use App\Core\Modules\ModuleServiceProvider;
use App\Settings\BrandingSettings;
use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use App\Settings\TaxSettings;
use Illuminate\Support\Facades\View;
use Modules\Storefront\Cookies\CookieConsent;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;
use Modules\Storefront\Pages\LegalPages;
use Modules\Storefront\Sitemap\SitemapRegistry;

class StorefrontServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'storefront';
    }

    public function register(): void
    {
        $this->app->singleton(SitemapRegistry::class);
        $this->app->singleton(LegalPages::class);
        $this->app->scoped(CookieConsent::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Trasy s jazykom obchodu dostanú v odkazoch predponu jazyka (/en/…).
        $this->app->make(LocalizedUrls::class)->markRoutesWith(SetStorefrontLocale::class);

        // Údaje o obchode dostupné vo všetkých šablónach vzhľadu.
        View::composer('theme::layouts.app', function (\Illuminate\View\View $view): void {
            // Adresy stránky v ostatných jazykoch (hreflang a prepínač jazyka).
            $view->with('alternateUrls', $this->app->make(AlternateUrls::class)->all());

            $view->with('cookieConsent', $this->app->make(CookieConsent::class));
        });

        View::composer('theme::*', function (\Illuminate\View\View $view): void {
            $view->with('shopLocales', $this->app->make(ShopLocales::class));

            $view->with([
                'shop' => app(GeneralSettings::class),
                'branding' => app(BrandingSettings::class),
                'company' => app(CompanySettings::class),
                'legalPages' => app(LegalPages::class),
                'vatPayer' => $this->vatPayer(),
            ]);
        });
    }

    private function vatPayer(): bool
    {
        try {
            return app(TaxSettings::class)->vat_payer;
        } catch (\Throwable) {
            return true;
        }
    }
}
