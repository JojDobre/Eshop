<?php

namespace Tests\Unit\Core;

use App\Core\Modules\Exceptions\ModuleException;
use App\Core\Modules\Module;
use App\Core\Modules\ModuleManager;
use App\Core\Modules\ModuleStateStore;
use PHPUnit\Framework\TestCase;

class ModuleManagerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/modules-'.bin2hex(random_bytes(4));
        mkdir($this->root.'/modules', 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function test_it_discovers_modules_sorted_by_name(): void
    {
        $this->module('shipping');
        $this->module('catalog', ['title' => ['sk' => 'Katalóg', 'en' => 'Catalog']]);

        $manager = $this->manager();

        $this->assertSame(['catalog', 'shipping'], array_keys($manager->all()));
        $this->assertSame('Katalóg', $manager->find('catalog')?->title('sk'));
        $this->assertSame('Catalog', $manager->find('catalog')?->title('de'));
    }

    public function test_invalid_manifests_are_reported_and_skipped(): void
    {
        mkdir($this->root.'/modules/broken');
        file_put_contents($this->root.'/modules/broken/module.json', '{not json');
        $this->module('catalog');

        $manager = $this->manager();

        $this->assertSame(['catalog'], array_keys($manager->all()));
        $this->assertArrayHasKey('broken', $manager->problems());
    }

    public function test_dependencies_are_booted_first(): void
    {
        $this->module('orders', ['requires' => ['modules' => ['catalog' => '^1.0', 'customers' => '^1.0']]]);
        $this->module('customers');
        $this->module('catalog', ['requires' => ['modules' => ['customers' => '^1.0']]]);

        $order = array_map(fn (Module $m) => $m->name, $this->manager()->active());

        $this->assertSame(['customers', 'catalog', 'orders'], $order);
    }

    public function test_module_with_missing_or_incompatible_dependency_is_not_loaded(): void
    {
        $this->module('blog', ['requires' => ['modules' => ['catalog' => '^2.0']]]);
        $this->module('catalog'); // verzia 1.0.0
        $this->module('reviews', ['requires' => ['modules' => ['blog' => '^1.0']]]);

        $manager = $this->manager();
        $active = array_map(fn (Module $m) => $m->name, $manager->active());

        $this->assertSame(['catalog'], $active);
        $this->assertNotNull($manager->problem('blog'));
        // Vyradenie „blog" vyradí aj modul, ktorý od neho závisí.
        $this->assertNotNull($manager->problem('reviews'));
    }

    public function test_module_requiring_newer_eshop_is_not_loaded(): void
    {
        $this->module('future', ['requires' => ['eshop' => '^2.0']]);

        $manager = $this->manager();

        $this->assertSame([], $manager->active());
        $this->assertStringContainsString('requires e-shop version', (string) $manager->problem('future'));
    }

    public function test_circular_dependencies_are_not_loaded(): void
    {
        $this->module('a', ['requires' => ['modules' => ['b' => '*']]]);
        $this->module('b', ['requires' => ['modules' => ['a' => '*']]]);
        $this->module('c');

        $manager = $this->manager();

        $this->assertSame(['c'], array_map(fn (Module $m) => $m->name, $manager->active()));
        $this->assertStringContainsString('Circular', (string) $manager->problem('a'));
    }

    public function test_disabled_by_default_modules_are_not_active(): void
    {
        $this->module('reservations', ['enabled' => false]);

        $this->assertFalse($this->manager()->isEnabled('reservations'));
        $this->assertSame([], $this->manager()->active());
    }

    public function test_enabling_requires_enabled_dependencies(): void
    {
        $this->module('catalog', ['enabled' => false]);
        $this->module('blog', ['enabled' => false, 'requires' => ['modules' => ['catalog' => '^1.0']]]);

        $manager = $this->manager();

        try {
            $manager->enable('blog');
            $this->fail('Expected exception.');
        } catch (ModuleException $e) {
            $this->assertSame(ModuleException::MISSING_DEPENDENCIES, $e->reason);
            $this->assertSame(['catalog'], $e->related);
        }

        $manager->enable('catalog');
        $manager->enable('blog');

        // Stav sa uložil a načíta ho aj nová inštancia.
        $this->assertTrue($this->manager()->isEnabled('blog'));
    }

    public function test_disabling_is_blocked_by_enabled_dependents(): void
    {
        $this->module('catalog');
        $this->module('blog', ['requires' => ['modules' => ['catalog' => '^1.0']]]);

        $manager = $this->manager();

        try {
            $manager->disable('catalog');
            $this->fail('Expected exception.');
        } catch (ModuleException $e) {
            $this->assertSame(ModuleException::HAS_DEPENDENTS, $e->reason);
            $this->assertSame(['blog'], $e->related);
        }

        $manager->disable('blog');
        $manager->disable('catalog');

        $this->assertFalse($manager->isEnabled('catalog'));
        $this->assertSame([], $manager->active());
    }

    public function test_core_modules_cannot_be_disabled(): void
    {
        $this->module('storefront', ['core' => true]);

        $this->expectExceptionObject(ModuleException::coreModule('storefront'));

        $this->manager()->disable('storefront');
    }

    public function test_safe_mode_loads_only_core_modules(): void
    {
        $this->module('storefront', ['core' => true]);
        $this->module('blog');

        $manager = $this->manager(safeMode: true);

        $this->assertSame(['storefront'], array_map(fn (Module $m) => $m->name, $manager->active()));
    }

    public function test_default_namespace_is_derived_from_directory_name(): void
    {
        $this->module('payment-stripe', [], 'payment-stripe');

        $this->assertSame('Modules\\PaymentStripe\\', $this->manager()->find('payment-stripe')?->namespace);
    }

    private function manager(bool $safeMode = false): ModuleManager
    {
        return new ModuleManager(
            paths: [$this->root.'/modules'],
            state: new ModuleStateStore($this->root.'/state/modules.json'),
            eshopVersion: '1.2.0',
            safeMode: $safeMode,
        );
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function module(string $name, array $manifest = [], ?string $directory = null): void
    {
        $path = $this->root.'/modules/'.($directory ?? ucfirst($name));
        mkdir($path, 0755, true);
        file_put_contents($path.'/module.json', json_encode(['name' => $name, 'version' => '1.0.0'] + $manifest));
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item !== '.' && $item !== '..') {
                $full = $path.'/'.$item;
                is_dir($full) ? $this->removeDirectory($full) : unlink($full);
            }
        }

        rmdir($path);
    }
}
