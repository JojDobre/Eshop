<?php

namespace Modules\Storefront;

use App\Core\Modules\ModuleServiceProvider;
use App\Settings\BrandingSettings;
use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\View;

class StorefrontServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'storefront';
    }

    public function boot(): void
    {
        parent::boot();

        // Údaje o obchode dostupné vo všetkých šablónach vzhľadu.
        View::composer('theme::*', function (\Illuminate\View\View $view): void {
            $view->with([
                'shop' => app(GeneralSettings::class),
                'branding' => app(BrandingSettings::class),
                'company' => app(CompanySettings::class),
            ]);
        });
    }
}
