<?php

namespace App\Providers;

use App\Core\Installer\Installation;
use App\Filament\Forms\MediaPicker;
use App\Models\User;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->forceConfiguredUrl();

        // Hlavný administrátor má všetky oprávnenia (štandardný mechanizmus Laravel Gates).
        // Výnimka: mazanie používateľov sa vždy riadi pravidlami v UserPolicy
        // (nikto nezmaže sám seba ani posledného hlavného administrátora).
        Gate::before(function ($user, string $ability, array $arguments) {
            if (! $user instanceof User || ! $user->isSuperAdmin()) {
                return null;
            }

            if ($ability === 'delete' && ($arguments[0] ?? null) instanceof User) {
                return null;
            }

            return true;
        });

        $this->configureAdminFormatting();
        MediaPicker::register();

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }

    /**
     * Po inštalácii sa všetky odkazy tvoria z adresy obchodu (APP_URL).
     *
     * Na webhostingu, kde požiadavky smeruje do public/ súbor .htaccess v koreni,
     * by inak Laravel pridával do odkazov „/public". Zároveň sa tak zjednotí
     * doména (napr. s „www" alebo bez) a protokol https.
     */
    private function forceConfiguredUrl(): void
    {
        $url = rtrim((string) config('app.url'), '/');

        if ($this->app->runningInConsole() || $url === '' || ! $this->app->make(Installation::class)->isInstalled()) {
            return;
        }

        URL::forceRootUrl($url);

        if (str_starts_with($url, 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Formát dátumov a čísel v administrácii podľa jazyka (napr. 6. 11. 2026 14:30).
     */
    private function configureAdminFormatting(): void
    {
        $date = fn () => app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y';
        $dateTime = fn () => app()->getLocale() === 'en' ? 'j M Y H:i' : 'j. n. Y H:i';
        $time = fn () => 'H:i';

        foreach ([Table::class, Schema::class] as $component) {
            $component::configureUsing(fn ($instance) => $instance
                ->defaultDateDisplayFormat($date)
                ->defaultDateTimeDisplayFormat($dateTime)
                ->defaultTimeDisplayFormat($time)
                ->defaultNumberLocale(fn () => app()->getLocale()));
        }
    }
}
