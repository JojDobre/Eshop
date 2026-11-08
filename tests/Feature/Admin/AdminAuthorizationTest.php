<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;
use Tests\Feature\InstalledTestCase;

/**
 * Každá stránka a sekcia administrácie musí byť chránená oprávnením (OWASP: chýbajúca kontrola prístupu).
 */
class AdminAuthorizationTest extends InstalledTestCase
{
    /** Stránky dostupné každému používateľovi administrácie. */
    private const PUBLIC_PAGES = ['Dashboard', 'EditProfile'];

    public function test_user_without_permissions_cannot_open_any_admin_section(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('bez-opravneni', 'web'));
        $this->actingAs($user);

        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $open = [];

        foreach ([...$panel->getResources(), ...$panel->getPages()] as $class) {
            if (in_array(class_basename($class), self::PUBLIC_PAGES, true)) {
                continue;
            }

            if ($class::canAccess()) {
                $open[] = $class;
            }
        }

        $this->assertSame([], $open, 'Bez oprávnenia sú dostupné: '.implode(', ', $open));
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/settings/general')->assertForbidden();
    }
}
