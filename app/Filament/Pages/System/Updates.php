<?php

namespace App\Filament\Pages\System;

use App\Core\Eshop;
use App\Core\Updates\UpdateException;
use App\Core\Updates\Updater;
use App\Filament\AdminGroup;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
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
            'feed' => filled(config('eshop.updates.feed_url')),
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
                ->visible(fn (): bool => filled(config('eshop.updates.feed_url')))
                ->action(function () use ($t): void {
                    try {
                        $feed = Http::timeout(10)->acceptJson()->get((string) config('eshop.updates.feed_url'))->throw()->json();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title($t('check_failed'))->body($e->getMessage())->send();

                        return;
                    }

                    $this->available = is_array($feed) && version_compare((string) ($feed['version'] ?? '0'), Eshop::VERSION, '>') ? $feed : null;
                    Notification::make()->success()->title($this->available ? $t('available', ['version' => $this->available['version']]) : $t('up_to_date'))->send();
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
