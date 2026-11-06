<?php

namespace App\Providers\Filament;

use App\Core\Installer\Installation;
use App\Filament\Pages\Auth\EditProfile;
use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\SetAdminLocale;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Throwable;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(EditProfile::class, isSimple: false)
            ->brandName(fn () => $this->general()?->shop_name ?? 'E-shop')
            ->brandLogo(fn () => $this->branding()?->logoUrl())
            ->darkModeBrandLogo(fn () => $this->branding()?->darkLogoUrl())
            ->brandLogoHeight('2rem')
            ->favicon(fn () => $this->branding()?->faviconUrl())
            ->colors(fn () => [
                'primary' => Color::hex($this->branding()?->primaryColor() ?? BrandingSettings::DEFAULT_PRIMARY_COLOR),
                'gray' => Color::Zinc,
            ])
            // Písmo je uložené priamo na hostingu (nie z externej služby), kvôli GDPR.
            ->font('Inter Variable', url: fn () => Vite::asset('resources/css/admin-font.css'), provider: LocalFontProvider::class)
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('7xl')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                EnsureInstalled::class,
                SetAdminLocale::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function general(): ?GeneralSettings
    {
        return $this->settings(GeneralSettings::class);
    }

    private function branding(): ?BrandingSettings
    {
        return $this->settings(BrandingSettings::class);
    }

    /**
     * Nastavenia sa dajú čítať až po inštalácii (pred ňou ešte neexistuje databáza).
     *
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    private function settings(string $class): ?object
    {
        if (! app(Installation::class)->isInstalled()) {
            return null;
        }

        try {
            return app($class);
        } catch (Throwable) {
            return null;
        }
    }
}
