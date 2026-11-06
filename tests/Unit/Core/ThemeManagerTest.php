<?php

namespace Tests\Unit\Core;

use App\Core\Themes\Theme;
use App\Core\Themes\ThemeManager;
use PHPUnit\Framework\TestCase;

class ThemeManagerTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/themes-'.bin2hex(random_bytes(4));
        $this->theme('default');
        $this->theme('base');
        $this->theme('child', ['parent' => 'base']);
        $this->theme('loop-a', ['parent' => 'loop-b']);
        $this->theme('loop-b', ['parent' => 'loop-a']);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root.'/*/theme.json') ?: [] as $file) {
            unlink($file);
            rmdir(dirname($file));
        }
        @rmdir($this->root);
    }

    public function test_child_theme_falls_back_to_parent_and_default(): void
    {
        $this->assertSame(['child', 'base', 'default'], $this->names($this->manager('child')));
    }

    public function test_unknown_active_theme_uses_default(): void
    {
        $this->assertSame(['default'], $this->names($this->manager('missing')));
        $this->assertSame(['default'], $this->names($this->manager(null)));
    }

    public function test_parent_loops_do_not_hang(): void
    {
        $this->assertSame(['loop-a', 'loop-b', 'default'], $this->names($this->manager('loop-a')));
    }

    public function test_module_override_paths_follow_the_chain(): void
    {
        $paths = $this->manager('child')->moduleOverridePaths('storefront');

        $this->assertSame($this->root.'/child/modules/storefront', $paths[0]);
        $this->assertSame($this->root.'/default/modules/storefront', $paths[2]);
    }

    /**
     * @return list<string>
     */
    private function names(ThemeManager $manager): array
    {
        return array_map(fn (Theme $theme) => $theme->name, $manager->chain());
    }

    private function manager(?string $active): ThemeManager
    {
        return new ThemeManager($this->root, 'default', fn () => $active);
    }

    /**
     * @param  array<string, mixed>  $manifest
     */
    private function theme(string $name, array $manifest = []): void
    {
        mkdir($this->root.'/'.$name, 0755, true);
        file_put_contents($this->root.'/'.$name.'/theme.json', json_encode(['name' => $name, 'version' => '1.0.0'] + $manifest));
    }
}
