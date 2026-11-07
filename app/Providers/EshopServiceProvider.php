<?php

namespace App\Providers;

use App\Core\Content\AlternateUrls;
use App\Core\Content\ContentLocale;
use App\Core\Content\LocalizedUrls;
use App\Core\Content\ShopLocales;
use App\Core\Eshop;
use App\Core\Installer\EnvironmentWriter;
use App\Core\Installer\Installation;
use App\Core\Installer\RequirementsChecker;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleManager;
use App\Core\Modules\ModuleStateStore;
use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use App\Core\Themes\ThemeManager;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use Composer\Autoload\ClassLoader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Translatable;
use Throwable;

/**
 * Jadro e-shopu: stav inštalácie, moduly a šablóny vzhľadu.
 */
class EshopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $statePath = rtrim((string) config('eshop.paths.state'), '/');

        $this->app->singleton(Installation::class, fn () => new Installation($statePath.'/installed.json'));

        $this->app->bind(EnvironmentWriter::class, fn ($app) => new EnvironmentWriter($app->environmentFilePath()));

        $this->app->bind(RequirementsChecker::class, fn ($app) => new RequirementsChecker($app->basePath()));

        $this->app->singleton(ModuleStateStore::class, fn () => new ModuleStateStore($statePath.'/modules.json'));

        $this->app->singleton(ModuleManager::class, fn ($app) => new ModuleManager(
            paths: (array) config('eshop.paths.modules'),
            state: $app->make(ModuleStateStore::class),
            eshopVersion: Eshop::VERSION,
            safeMode: (bool) config('eshop.safe_mode'),
        ));

        $this->app->singleton(ContentLocale::class);
        $this->app->scoped(ShopLocales::class);
        $this->app->singleton(LocalizedUrls::class);
        $this->app->scoped(AlternateUrls::class);

        // Mena zákazníka sa nastavuje pre každú požiadavku obchodu (SetStorefrontLocale).
        $this->app->scoped(CurrencyContext::class);
        $this->app->scoped(Currencies::class);

        $this->app->singleton(EmailTemplates::class);

        $this->app->singleton(ThemeManager::class, fn ($app) => new ThemeManager(
            themesPath: (string) config('eshop.paths.themes'),
            defaultTheme: (string) config('eshop.default_theme'),
            activeResolver: fn () => $this->activeThemeName(),
        ));

        $this->registerModules();
    }

    public function boot(): void
    {
        $this->applyMailSettings();

        // Ak obsah nemá preklad v jazyku obchodu (napr. po zmene jazyka), použije sa ktorýkoľvek
        // dostupný preklad namiesto prázdnej hodnoty.
        $this->app->make(Translatable::class)->fallback(fallbackAny: true);

        // Predpona jazyka v odkazoch obchodu (/en/…), pozri LocalizedUrls.
        URL::formatPathUsing(
            fn (string $path, $route = null) => $this->app->make(LocalizedUrls::class)->formatPath($path, $route),
        );

        $themes = $this->app->make(ThemeManager::class);

        View::addNamespace('theme', $themes->viewPaths());

        if ($langPath = $themes->langPath()) {
            $this->loadTranslationsFrom($langPath, 'theme');
        }
    }

    /**
     * Zaregistruje Service Provider každého zapnutého modulu v poradí podľa závislostí.
     *
     * Chyba pri registrácii jedného modulu nezhodí celý obchod: zapíše sa do logu,
     * modul sa označí ako problémový a ostatné moduly sa načítajú ďalej.
     */
    private function registerModules(): void
    {
        $manager = $this->app->make(ModuleManager::class);
        $loader = array_values(ClassLoader::getRegisteredLoaders())[0] ?? null;

        foreach ($manager->active() as $module) {
            // Triedy modulu sa načítavajú automaticky z jeho adresára src/ (bez úpravy composer.json).
            if ($loader !== null && $module->namespace !== null && is_dir($module->sourcePath())) {
                $loader->addPsr4($module->namespace, $module->sourcePath());
            }

            if ($module->provider === null) {
                continue;
            }

            if (! class_exists($module->provider)) {
                $manager->reportProblem($module->name, "Service provider [{$module->provider}] was not found.");

                continue;
            }

            try {
                $this->app->register($module->provider);
            } catch (Throwable $e) {
                $manager->reportProblem($module->name, $e->getMessage());

                Log::error("Module [{$module->name}] failed to register.", [
                    'module' => $module->name,
                    'exception' => $e,
                ]);
            }
        }
    }

    /**
     * Nastavenia odosielania e-mailov z administrácie majú prednosť pred súborom .env.
     */
    private function applyMailSettings(): void
    {
        if (! $this->app->make(Installation::class)->isInstalled()) {
            return;
        }

        try {
            $this->app->make(MailSettings::class)->applyToConfig($this->app->make(GeneralSettings::class));
        } catch (Throwable) {
            // Databáza nie je dostupná alebo ešte neprebehli migrácie (napr. počas aktualizácie).
        }
    }

    private function activeThemeName(): ?string
    {
        if (! $this->app->make(Installation::class)->isInstalled()) {
            return null;
        }

        try {
            return $this->app->make(GeneralSettings::class)->theme;
        } catch (Throwable) {
            // Databáza nie je dostupná: obchod sa zobrazí v predvolenej šablóne.
            return null;
        }
    }
}
