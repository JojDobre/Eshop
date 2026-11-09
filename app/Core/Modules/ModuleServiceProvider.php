<?php

namespace App\Core\Modules;

use App\Core\Themes\ThemeManager;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;
use Spatie\LaravelSettings\Settings;
use Spatie\LaravelSettings\Support\SettingsCacheFactory;

/**
 * Základná trieda pre Service Provider modulu.
 *
 * Podľa konvencie automaticky načíta z adresára modulu:
 * - database/migrations  → štandardné Laravel migrácie
 * - resources/views      → šablóny pod menným priestorom názvu modulu, napr. view('storefront::home')
 *                          (aktívna téma ich môže prepísať v themes/{téma}/modules/{modul}/…)
 * - lang                 → preklady pod menným priestorom modulu, napr. __('storefront::shop.title')
 * - routes/web.php       → webové routy (s middleware skupinou „web")
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Názov modulu (rovnaký ako „name" v module.json).
     */
    abstract protected function moduleName(): string;

    public function boot(): void
    {
        $this->bootModuleResources();
    }

    protected function bootModuleResources(): void
    {
        $path = $this->modulePath();
        $name = $this->moduleName();

        if (is_dir($path.'/database/migrations')) {
            $this->loadMigrationsFrom($path.'/database/migrations');
        }

        if (is_dir($path.'/resources/views')) {
            $paths = [$path.'/resources/views'];

            if ($this->app->bound(ThemeManager::class)) {
                $paths = [...$this->app->make(ThemeManager::class)->moduleOverridePaths($name), ...$paths];
            }

            $this->loadViewsFrom($paths, $name);
        }

        $this->registerModuleSettings($path);

        if (is_dir($path.'/lang')) {
            $this->loadTranslationsFrom($path.'/lang', $name);
        }

        if (is_file($path.'/routes/web.php') && ! $this->app->routesAreCached()) {
            $this->app['router']->middleware('web')->group($path.'/routes/web.php');
        }
    }

    /**
     * Nastavenia modulu (src/*Settings.php) ako jedna inštancia na požiadavku s cache – rovnako ako
     * nastavenia jadra. Bez toho by sa pri každom čítaní znova načítali z databázy.
     */
    protected function registerModuleSettings(string $path): void
    {
        $namespace = (new ReflectionClass($this))->getNamespaceName();

        foreach (glob($path.'/src/*Settings.php') ?: [] as $file) {
            $class = $namespace.'\\'.basename($file, '.php');

            if (! class_exists($class) || ! is_subclass_of($class, Settings::class)) {
                continue;
            }

            $this->app->scoped($class, function () use ($class) {
                $cache = $this->app->make(SettingsCacheFactory::class)->build($class::repository());

                try {
                    if ($cache->isEnabled() && ($cached = $cache->get($class)) !== null) {
                        return $cached;
                    }
                } catch (\Throwable) {
                    // Poškodená cache: načíta sa z databázy.
                }

                return new $class;
            });
        }
    }

    /**
     * Koreňový adresár modulu (adresár so súborom module.json).
     */
    protected function modulePath(string $path = ''): string
    {
        $directory = dirname((string) (new ReflectionClass($this))->getFileName());

        // Provider býva v src/, koreň modulu je o úroveň vyššie.
        while (! is_file($directory.'/module.json') && dirname($directory) !== $directory) {
            $directory = dirname($directory);
        }

        return $path === '' ? $directory : $directory.'/'.ltrim($path, '/');
    }
}
