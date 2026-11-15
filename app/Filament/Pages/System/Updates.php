<?php

namespace App\Filament\Pages\System;

use App\Core\Eshop;
use App\Core\Updates\UpdateChecker;
use App\Core\Updates\UpdateException;
use App\Core\Updates\Updater;
use App\Filament\AdminGroup;
use App\Models\User;
use App\Settings\SystemSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Throwable;
use UnitEnum;

/**
 * Aktualizácie e-shopu: nahratie podpísaného balíka, inštalácia so zálohou a vrátenie poslednej aktualizácie.
 */
class Updates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static ?int $navigationSort = 92;

    protected static ?string $slug = 'system/updates';

    protected string $view = 'filament.pages.system.updates';

    /** @var array{version: string, notes?: ?string, url?: ?string}|null */
    public ?array $available = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isSuperAdmin();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::System;
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.updates.title');
    }

    /** Odznak v menu, keď je známa novšia verzia (bez sieťovej požiadavky). */
    public static function getNavigationBadge(): ?string
    {
        return rescue(fn () => app(UpdateChecker::class)->cachedAvailable()['version'] ?? null, report: false);
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public function mount(): void
    {
        // Pri otvorení stránky stačí výsledok poslednej kontroly (najviac 6 hodín starý).
        $this->available = rescue(fn () => app(UpdateChecker::class)->available(), report: false);
    }

    public function getTitle(): string
    {
        return __('admin.updates.title');
    }

    protected function getViewData(): array
    {
        return [
            'version' => Eshop::VERSION,
            'lastUpdate' => app(Updater::class)->lastUpdate(),
            'signed' => Updater::publicKeys() !== [],
            'autoInstall' => app(SystemSettings::class)->updates_auto_install,
            'source' => filled(config('eshop.updates.feed_url')) ? (string) config('eshop.updates.feed_url') : 'github.com/'.config('eshop.updates.github_repo'),
        ];
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key, array $replace = []) => __('admin.updates.'.$key, $replace);

        return [
            Action::make('check')
                ->label($t('check'))
                ->icon(Heroicon::OutlinedMagnifyingGlass)
                ->color('gray')
                ->action(function () use ($t): void {
                    $checker = app(UpdateChecker::class);

                    if ($checker->latest(true) === null) {
                        Notification::make()->danger()->title($t('check_failed'))->send();

                        return;
                    }

                    $this->available = $checker->available();
                    Notification::make()->success()->title($this->available ? $t('available', ['version' => $this->available['version']]) : $t('up_to_date'))->send();
                }),
            Action::make('installAvailable')
                ->label(fn () => $t('install_now', ['version' => $this->available['version'] ?? '']))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (): bool => filled($this->available['download'] ?? null))
                ->requiresConfirmation()
                ->modalHeading(fn () => $t('install_now', ['version' => $this->available['version'] ?? '']))
                ->modalDescription($t('install_now_confirm'))
                ->action(function () use ($t): void {
                    try {
                        $package = app(UpdateChecker::class)->installAvailable();
                    } catch (UpdateException $e) {
                        Notification::make()->danger()->title($t('failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    Notification::make()->success()->title($t('installed', ['version' => $package->version()]))->persistent()->send();
                    $this->redirect(self::getUrl());
                }),
            Action::make('settings')
                ->label($t('settings'))
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('gray')
                ->fillForm(fn () => [
                    'updates_auto_install' => app(SystemSettings::class)->updates_auto_install,
                    'updates_notify' => app(SystemSettings::class)->updates_notify,
                ])
                ->schema([
                    Toggle::make('updates_notify')->label($t('notify'))->helperText($t('notify_help')),
                    Toggle::make('updates_auto_install')->label($t('auto_install'))->helperText($t('auto_install_help')),
                ])
                ->action(function (array $data) use ($t): void {
                    $settings = app(SystemSettings::class);
                    $settings->updates_notify = (bool) $data['updates_notify'];
                    $settings->updates_auto_install = (bool) $data['updates_auto_install'];
                    $settings->save();
                    Notification::make()->success()->title($t('settings_saved'))->send();
                }),
            Action::make('upload')
                ->label($t('upload'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->modalDescription($t('upload_help'))
                ->schema([
                    FileUpload::make('package')->label($t('package'))->disk('local')->directory('update-uploads')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])->maxSize(512000)->required(),
                    Toggle::make('allow_unsigned')->label($t('allow_unsigned'))->helperText($t('allow_unsigned_help')),
                ])
                ->action(function (array $data) use ($t): void {
                    $path = storage_path('app/private/'.$data['package']);

                    try {
                        $package = app(Updater::class)->install($path, (bool) ($data['allow_unsigned'] ?? false));
                    } catch (UpdateException $e) {
                        Notification::make()->danger()->title($t('failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    } finally {
                        File::delete($path);
                    }

                    Notification::make()->success()->title($t('installed', ['version' => $package->version()]))->persistent()->send();
                    $this->redirect(self::getUrl());
                }),
            Action::make('rollback')
                ->label($t('rollback'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('warning')
                ->visible(fn (): bool => app(Updater::class)->lastUpdate() !== null)
                ->requiresConfirmation()
                ->modalDescription(fn () => $t('rollback_confirm', ['version' => app(Updater::class)->lastUpdate()['from'] ?? '']))
                ->action(function () use ($t): void {
                    try {
                        app(Updater::class)->rollback();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title($t('rollback_failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    Notification::make()->success()->title($t('rolled_back'))->persistent()->send();
                    $this->redirect(self::getUrl());
                }),
        ];
    }
}
