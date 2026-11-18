<?php

namespace Tests\Feature;

use Tests\TestCase;

class MaintenancePageTest extends TestCase
{
    public function test_maintenance_page_explains_the_update(): void
    {
        $html = view('errors.503')->render();

        $this->assertStringContainsString(__('maintenance.heading'), $html);
        $this->assertStringContainsString('http-equiv="refresh"', $html);
        $this->assertStringNotContainsString('Service Unavailable', $html);
    }
}
