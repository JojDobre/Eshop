<?php

namespace App\Core\Installer;

use App\Core\Eshop;
use App\Models\User;
use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Vykoná samotnú inštaláciu: migrácie databázy, nastavenia obchodu a administrátora.
 *
 * Inštaláciu je možné bezpečne zopakovať, ak niektorý krok zlyhá
 * (migrácie aj vytvorenie rolí a používateľa sú idempotentné).
 */
class Installer
{
    public function __construct(
        private readonly Installation $installation,
        private readonly EnvironmentWriter $environment,
    ) {}

    /**
     * @param  array{shop_name: string, contact_email: string, locale: string, currency: string, country: string, url: string}  $shop
     * @param  array{name: string, email: string, password: string}  $admin
     */
    public function install(array $shop, array $admin): User
    {
        if ($this->installation->isInstalled()) {
            throw new RuntimeException('The e-shop is already installed.');
        }

        @set_time_limit(300);

        $exitCode = Artisan::call('migrate', ['--force' => true]);

        if ($exitCode !== 0) {
            throw new RuntimeException(trim(Artisan::output()) ?: 'Database migration failed.');
        }

        $user = DB::transaction(function () use ($shop, $admin): User {
            $this->saveSettings($shop);

            return $this->createAdministrator($admin, $shop['locale']);
        });

        $this->environment->set([
            'APP_NAME' => $shop['shop_name'],
            'APP_URL' => rtrim($shop['url'], '/'),
            'APP_LOCALE' => $shop['locale'],
            'APP_ENV' => 'production',
            'APP_DEBUG' => false,
            'MAIL_FROM_ADDRESS' => $shop['contact_email'],
            'MAIL_FROM_NAME' => $shop['shop_name'],
        ]);

        $this->installation->markInstalled(Eshop::VERSION);

        Artisan::call('optimize:clear');

        return $user;
    }

    /**
     * @param  array{shop_name: string, contact_email: string, locale: string, currency: string, country: string, url: string}  $shop
     */
    private function saveSettings(array $shop): void
    {
        $general = app(GeneralSettings::class);
        $general->shop_name = $shop['shop_name'];
        $general->contact_email = $shop['contact_email'];
        $general->default_locale = $shop['locale'];
        $general->default_currency = $shop['currency'];
        $general->country = $shop['country'];
        $general->save();

        $company = app(CompanySettings::class);
        $company->country = $shop['country'];
        $company->save();
    }

    /**
     * @param  array{name: string, email: string, password: string}  $admin
     */
    private function createAdministrator(array $admin, string $locale): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::findOrCreate(User::ROLE_SUPER_ADMIN, 'web');

        $user = User::query()->updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'password' => $admin['password'],
                'locale' => $locale,
                'is_active' => true,
            ],
        );

        $user->assignRole($role);

        return $user;
    }
}
