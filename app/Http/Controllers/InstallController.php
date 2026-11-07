<?php

namespace App\Http\Controllers;

use App\Core\Installer\DatabaseChecker;
use App\Core\Installer\EnvironmentWriter;
use App\Core\Installer\Installer;
use App\Core\Installer\RequirementsChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

/**
 * Webový inštalátor: vitajte → požiadavky → databáza → obchod a administrátor → hotovo.
 */
class InstallController extends Controller
{
    public function welcome(): View
    {
        return view('installer.welcome', [
            'locales' => config('eshop.locales'),
            'step' => 1,
        ]);
    }

    public function setLocale(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', Rule::in(array_keys(config('eshop.locales')))],
        ]);

        $request->session()->put('installer.locale', $data['locale']);

        return redirect()->route('install.requirements');
    }

    public function requirements(RequirementsChecker $checker): View
    {
        $results = $checker->check();

        return view('installer.requirements', [
            'groups' => collect($results)->groupBy('group'),
            'passes' => $checker->passes($results),
            'step' => 2,
        ]);
    }

    public function database(RequirementsChecker $checker): View|RedirectResponse
    {
        if (! $checker->passes()) {
            return redirect()->route('install.requirements');
        }

        return view('installer.database', [
            'defaults' => [
                'host' => old('host', config('database.connections.mysql.host') ?: 'localhost'),
                'port' => old('port', config('database.connections.mysql.port') ?: 3306),
                'database' => old('database', config('database.connections.mysql.database')),
                'username' => old('username', config('database.connections.mysql.username')),
                'prefix' => old('prefix', config('database.connections.mysql.prefix')),
            ],
            'step' => 3,
        ]);
    }

    public function saveDatabase(Request $request, DatabaseChecker $checker, EnvironmentWriter $environment): RedirectResponse
    {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'string', 'max:64'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'prefix' => ['nullable', 'string', 'max:20', 'regex:/^[A-Za-z0-9_]*$/'],
        ]);

        $result = $checker->check($data);

        if (! $result->success) {
            return back()->withInput($request->except('password'))->withErrors(['connection' => $result->error]);
        }

        if ($result->containsInstallation && ! $request->boolean('confirm_existing')) {
            return back()->withInput($request->except('password'))
                ->with('confirm_existing', true)
                ->withErrors(['connection' => __('installer.database.existing_installation')]);
        }

        $environment->set([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['host'],
            'DB_PORT' => (string) $data['port'],
            'DB_DATABASE' => $data['database'],
            'DB_USERNAME' => $data['username'],
            'DB_PASSWORD' => (string) ($data['password'] ?? ''),
            'DB_PREFIX' => (string) ($data['prefix'] ?? ''),
        ]);

        $request->session()->put('installer.database', [
            'version' => $result->version,
            'limited' => $result->limitedSupport,
        ]);

        return redirect()->route('install.shop');
    }

    public function shop(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('installer.database') || ! $this->databaseIsReachable()) {
            return redirect()->route('install.database');
        }

        return view('installer.shop', [
            'locales' => config('eshop.locales'),
            'currencies' => config('eshop.currencies'),
            'countries' => config('eshop.countries'),
            'defaults' => [
                'url' => old('url', preg_replace('#/public$#', '', $request->root())),
                'locale' => old('locale', app()->getLocale()),
                'currency' => old('currency', app()->getLocale() === 'cs' ? 'CZK' : 'EUR'),
                'country' => old('country', match (app()->getLocale()) {
                    'cs' => 'CZ',
                    'en' => 'GB',
                    default => 'SK',
                }),
            ],
            'database' => $request->session()->get('installer.database'),
            'step' => 4,
        ]);
    }

    public function install(Request $request, Installer $installer): RedirectResponse
    {
        $data = $request->validate([
            'shop_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:255'],
            'url' => ['required', 'url', 'max:255'],
            'locale' => ['required', Rule::in(array_keys(config('eshop.locales')))],
            'currency' => ['required', Rule::in(array_keys(config('eshop.currencies')))],
            'country' => ['required', Rule::in(config('eshop.countries'))],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
            'demo_data' => ['nullable', 'boolean'],
        ]);

        try {
            $installer->install(
                shop: [
                    'shop_name' => $data['shop_name'],
                    'contact_email' => $data['contact_email'],
                    'url' => $data['url'],
                    'locale' => $data['locale'],
                    'currency' => $data['currency'],
                    'country' => $data['country'],
                ],
                admin: [
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => $data['admin_password'],
                ],
                withDemoData: (bool) ($data['demo_data'] ?? false),
            );
        } catch (Throwable $e) {
            Log::error('Installation failed.', ['exception' => $e]);

            return back()->withInput($request->except(['admin_password', 'admin_password_confirmation']))
                ->withErrors(['install' => __('installer.shop.failed', ['message' => $e->getMessage()])]);
        }

        $request->session()->forget('installer.database');
        $request->session()->put('installer.completed', [
            'admin_email' => $data['admin_email'],
            'url' => rtrim($data['url'], '/'),
        ]);

        return redirect()->route('install.done');
    }

    public function done(Request $request): View
    {
        $completed = $request->session()->pull('installer.completed');

        abort_if($completed === null, 404);

        return view('installer.done', [
            'completed' => $completed,
            'cronCommand' => sprintf('* * * * * %s %s schedule:run >> /dev/null 2>&1', PHP_BINARY ?: 'php', base_path('artisan')),
            'step' => 5,
        ]);
    }

    private function databaseIsReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
