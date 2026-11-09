<?php

namespace Tests\Feature\Admin;

use App\Filament\AdminGroup;
use App\Filament\Pages\Modules;
use App\Filament\Pages\Settings\ManageAdminMenu;
use App\Filament\Pages\Settings\ManageBrandingSettings;
use App\Filament\Pages\Settings\ManageGeneralSettings;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Settings\BrandingSettings;
use App\Settings\GeneralSettings;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\Feature\InstalledTestCase;

class AdminPanelTest extends InstalledTestCase
{
    public function test_login_page_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_super_admin_can_open_all_admin_pages(): void
    {
        $this->actingAs($this->superAdmin());

        foreach (['/admin', '/admin/settings/general', '/admin/settings/branding', '/admin/settings/company', '/admin/users', '/admin/system/modules', '/admin/profile'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_user_without_role_or_inactive_user_cannot_access_admin(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();

        $inactive = $this->superAdmin(['is_active' => false]);
        $this->actingAs($inactive)->get('/admin')->assertForbidden();
    }

    public function test_admin_uses_language_from_user_profile(): void
    {
        $this->actingAs($this->superAdmin(['locale' => 'cs']))
            ->get('/admin/settings/company')
            ->assertSee('Firemní údaje');

        $this->actingAs($this->superAdmin(['locale' => 'en']))
            ->get('/admin/settings/company')
            ->assertSee('Company details');
    }

    public function test_general_settings_can_be_saved(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageGeneralSettings::class)
            ->fillForm([
                'shop_name' => 'Nový názov',
                'tagline' => 'Najlepšia káva v meste',
                'contact_email' => 'kontakt@obchod.sk',
                'default_locale' => 'sk',
                'default_currency' => 'EUR',
                'country' => 'SK',
                'theme' => 'default',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Nový názov', app(GeneralSettings::class)->refresh()->shop_name);
    }

    public function test_invalid_brand_color_is_rejected(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageBrandingSettings::class)
            ->fillForm(['primary_color' => 'red;}</style><script>'])
            ->call('save')
            ->assertHasFormErrors(['primary_color']);

        $this->assertSame('#4f46e5', app(BrandingSettings::class)->refresh()->primary_color);
    }

    public function test_admin_can_create_another_user(): void
    {
        $this->actingAs($this->superAdmin());
        $role = Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Eva',
                'email' => 'eva@obchod.sk',
                'password' => 'Heslo12345',
                'locale' => 'sk',
                'roles' => [$role->id],
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertTrue(User::where('email', 'eva@obchod.sk')->firstOrFail()->isSuperAdmin());
    }

    public function test_admin_cannot_delete_self_or_last_super_admin(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin);

        $this->assertFalse($admin->can('delete', $admin));

        $other = $this->superAdmin();
        $this->assertTrue($admin->can('delete', $other));

        Livewire::test(ListUsers::class)->assertCanSeeTableRecords([$admin, $other]);
    }

    public function test_modules_page_lists_modules(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(Modules::class)
            ->assertOk()
            ->assertSee('Obchod');
    }

    public function test_menu_items_can_be_hidden_and_settings_are_grouped(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageAdminMenu::class)
            ->assertSee(__('admin.users.plural'))
            ->set('data.hidden.'.md5(AdminGroup::System->label()), ['users'])
            ->call('save');

        $this->assertSame(['users'], app(GeneralSettings::class)->admin_hidden_navigation);
        $menu = $this->get('/admin/settings/general')->assertOk()->getContent();
        $this->assertStringNotContainsString('href="'.url('/admin/users').'"', $menu);
        // Skrytá stránka je dostupná priamo, podpoložky „Obchod“ sú v menu pod ním.
        $this->get('/admin/users')->assertOk();
        $this->assertStringContainsString('href="'.url('/admin/settings/branding').'"', $menu);
    }
}
