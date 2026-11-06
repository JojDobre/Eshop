<?php

namespace Tests\Unit\Core;

use App\Core\Installer\Requirement;
use App\Core\Installer\RequirementsChecker;
use PHPUnit\Framework\TestCase;

class RequirementsCheckerTest extends TestCase
{
    public function test_it_converts_php_ini_sizes_to_bytes(): void
    {
        $this->assertSame(128 * 1024 * 1024, RequirementsChecker::bytes('128M'));
        $this->assertSame(2 * 1024 ** 3, RequirementsChecker::bytes('2G'));
        $this->assertSame(512 * 1024, RequirementsChecker::bytes('512k'));
        $this->assertSame(-1, RequirementsChecker::bytes('-1'));
    }

    public function test_only_required_items_decide_whether_requirements_pass(): void
    {
        $checker = new RequirementsChecker(sys_get_temp_dir());

        $this->assertTrue($checker->passes([
            new Requirement('a', 'a', true),
            new Requirement('b', 'b', false, required: false),
        ]));

        $this->assertFalse($checker->passes([
            new Requirement('a', 'a', false),
        ]));
    }

    public function test_it_reports_the_php_version(): void
    {
        $results = (new RequirementsChecker(sys_get_temp_dir()))->check();

        $php = collect($results)->firstWhere('key', 'php');

        $this->assertNotNull($php);
        $this->assertSame(PHP_VERSION, $php->current);
    }
}
