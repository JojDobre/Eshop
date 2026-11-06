<?php

namespace Tests;

use App\Core\Installer\EnvironmentWriter;
use App\Core\Installer\Installation;
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

        // Testy nikdy nezapisujú do skutočného .env ani do stavu inštalácie vývojára.
        $this->app->instance(Installation::class, new Installation($this->tempPath.'/installed.json'));
        $this->app->bind(EnvironmentWriter::class, fn () => new EnvironmentWriter($this->tempPath.'/.env'));

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
