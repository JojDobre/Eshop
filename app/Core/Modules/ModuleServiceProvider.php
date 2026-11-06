<?php

namespace App\Core\Modules;

use App\Core\Themes\ThemeManager;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

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

        if (is_dir($path.'/lang')) {
            $this->loadTranslationsFrom($path.'/lang', $name);
        }

        if (is_file($path.'/routes/web.php') && ! $this->app->routesAreCached()) {
            $this->app['router']->middleware('web')->group($path.'/routes/web.php');
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
