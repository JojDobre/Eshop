<?php

namespace App\Filament\Pages\System;

use App\Core\System\CronHeartbeat;
use App\Core\System\SystemStatus;
use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Stav systému: verzie, požiadavky hostingu, cron, e-maily a zálohy na jednom mieste.
 */
class SystemStatusPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?int $navigationSort = 90;

    protected static ?string $slug = 'system/status';

    protected string $view = 'filament.pages.system.status';

    public static function getNavigationLabel(): string
    {
        return __('admin.status.title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::System;
    }

    public function getTitle(): string
    {
        return __('admin.status.title');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'checks' => app(SystemStatus::class)->checks(),
            'cronCommand' => '* * * * * cd '.base_path().' && php artisan schedule:run >> /dev/null 2>&1',
            'webCronUrl' => route('cron.web', CronHeartbeat::webToken()),
        ];
    }
}
