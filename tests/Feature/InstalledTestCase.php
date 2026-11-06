<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Základ pre testy nainštalovaného obchodu.
 */
abstract class InstalledTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->markInstalled();
    }

    protected function superAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole(Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web'));

        return $user;
    }
}
