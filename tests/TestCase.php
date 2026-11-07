<?php

namespace Tests;

use App\Core\Eshop;
use App\Core\Installer\EnvironmentWriter;
use App\Core\Installer\Installation;
use App\Core\Modules\ModuleManager;
use App\Core\Modules\ModuleStateStore;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    /** Dočasný adresár testu (stav inštalácie, .env, moduly). */
    protected string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempPath = sys_get_temp_dir().'/eshop-test-'.bin2hex(random_bytes(6));
        File::ensureDirectoryExists($this->tempPath);

        // Testy nikdy nezapisujú do skutočného .env, stavu inštalácie ani stavu modulov vývojára.
        $this->app->instance(Installation::class, new Installation($this->tempPath.'/installed.json'));
        $this->app->bind(EnvironmentWriter::class, fn () => new EnvironmentWriter($this->tempPath.'/.env'));

        // Zapnutie a vypnutie modulov v testoch sa zapisuje do dočasného súboru.
        $modules = new ModuleStateStore($this->tempPath.'/modules.json');
        $this->app->instance(ModuleStateStore::class, $modules);
        $this->app->instance(ModuleManager::class, new ModuleManager(
            paths: (array) config('eshop.paths.modules'),
            state: $modules,
            eshopVersion: Eshop::VERSION,
        ));

        $this->withoutVite();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    protected function markInstalled(): void
    {
        $this->app->make(Installation::class)->markInstalled('0.1.0');
    }
}
