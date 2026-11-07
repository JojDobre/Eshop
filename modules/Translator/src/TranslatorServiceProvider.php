<?php

namespace Modules\Translator;

use App\Core\Content\MachineTranslator;
use App\Core\Installer\Installation;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class TranslatorServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'translator';
    }

    public function register(): void
    {
        $this->app->singleton(MachineTranslationService::class);
        $this->app->bind(MachineTranslator::class, MachineTranslationService::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Hromadný preklad po dávkach (každú minútu, ak beží cron).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(TranslationQueue::class)->process(20))
                ->name('translator:queue')
                ->everyMinute()
                ->withoutOverlapping(10)
                ->when(fn () => app(Installation::class)->isInstalled() && app(TranslationQueue::class)->pending() > 0);
        });
    }
}
