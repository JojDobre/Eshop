<?php

namespace App\Filament\Pages\System;

use App\Core\Backup\BackupManager;
use App\Core\Backup\BackupRestorer;
use App\Core\Backup\BackupRunner;
use App\Core\Backup\BackupVerifier;
use App\Core\Backup\RemoteBackups;
use App\Filament\AdminGroup;
use App\Settings\SystemSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;
use UnitEnum;

/**
 * Zálohy obchodu: ručné vytvorenie, stiahnutie, mazanie a nastavenie automatických záloh.
 */
class Backups extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 91;

    protected static ?string $slug = 'system/backups';

    protected string $view = 'filament.pages.system.table';

    public static function getNavigationLabel(): string
    {
        return __('admin.backups.title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::System;
    }

    public function getTitle(): string
    {
        return __('admin.backups.title');
    }

    public function getSubheading(): ?string
    {
        $check = app(SystemSettings::class)->backups_last_check;

        if ($check === null) {
            return __('admin.backups.intro');
        }

        return __('admin.backups.intro').' '.__($check['ok'] ? 'admin.backups.last_check_ok' : 'admin.backups.last_check_failed', [
            'name' => $check['name'], 'date' => Carbon::parse($check['checked_at'])->format('j. n. Y H:i'), 'message' => $check['message'],
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage') ?? false;
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('admin.backups.'.$key);

        return $table
            ->records(fn () => collect(app(BackupManager::class)->all())->keyBy('name')->all())
            ->columns([
                TextColumn::make('name')->label($t('file'))->fontFamily('mono'),
                TextColumn::make('created_at')->label($t('created_at'))->dateTime(),
                TextColumn::make('size')->label($t('size'))->formatStateUsing(fn (int $state) => Number::fileSize($state, 1)),
            ])
            ->recordActions([
                Action::make('download')->label($t('download'))->icon(Heroicon::OutlinedArrowDownTray)
                    ->action(fn (array $record): BinaryFileResponse => response()->download(app(BackupManager::class)->path($record['name']))),
                Action::make('verify')->label($t('verify'))->icon(Heroicon::OutlinedShieldCheck)
                    ->action(function (array $record) use ($t): void {
                        $result = app(BackupVerifier::class)->verify($record['name']);
                        Notification::make()->{$result['ok'] ? 'success' : 'danger'}()->title($t($result['ok'] ? 'check_passed' : 'check_failed'))->body($result['message'])->send();
                    }),
                Action::make('restore')->label($t('restore'))->icon(Heroicon::OutlinedArrowUturnLeft)->color('warning')
                    ->modalDescription($t('restore_warning'))
                    ->schema([
                        Toggle::make('with_files')->label($t('restore_files'))->default(true),
                        TextInput::make('confirm')->label($t('restore_confirm'))->required()->in([$t('restore_word')]),
                    ])
                    ->action(function (array $record, array $data) use ($t): void {
                        @set_time_limit(0);

                        try {
                            $result = app(BackupRestorer::class)->restore($record['name'], (bool) $data['with_files']);
                        } catch (Throwable $e) {
                            report($e);
                            Notification::make()->danger()->title($t('restore_failed'))->body($e->getMessage())->persistent()->send();

                            return;
                        }

                        Notification::make()->success()->title($t('restored'))->body($t('restored_body', ['safety' => $result['safety_backup'] ?? '–']))->persistent()->send();
                        $this->redirect(self::getUrl());
                    }),
                Action::make('delete')->label($t('delete'))->icon(Heroicon::OutlinedTrash)->color('danger')->requiresConfirmation()
                    ->action(function (array $record) use ($t): void {
                        app(BackupManager::class)->delete($record['name']);
                        Notification::make()->success()->title($t('deleted'))->send();
                    }),
            ])
            ->emptyStateHeading($t('empty'))
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key) => __('admin.backups.'.$key);

        return [
            Action::make('settings')
                ->label($t('settings'))
                ->icon(Heroicon::OutlinedCog6Tooth)
                ->color('gray')
                ->fillForm(fn () => collect(app(SystemSettings::class)->toArray())->except(['backups_last_check', 'backups_password', 'backups_remote_password'])->all())
                ->schema([
                    Section::make()->columns(2)->schema([
                        Toggle::make('backups_enabled')->label($t('enabled'))->helperText($t('enabled_help'))->columnSpanFull(),
                        Select::make('backups_frequency')->label($t('frequency'))->selectablePlaceholder(false)
                            ->options(['daily' => $t('frequency_daily'), 'weekly' => $t('frequency_weekly')]),
                        TextInput::make('backups_keep')->label($t('keep'))->integer()->minValue(1)->maxValue(60)->required(),
                        Toggle::make('backups_include_files')->label($t('include_files')),
                        TextInput::make('backups_password')->label($t('password'))->helperText($t('password_help'))->password()->revealable()->maxLength(100),
                    ]),
                    Section::make($t('remote'))->description($t('remote_help'))->columns(2)->schema([
                        Select::make('backups_remote')->label($t('remote_type'))->selectablePlaceholder(false)->live()
                            ->options(['none' => $t('remote_none'), 'ftp' => 'FTP', 'sftp' => 'SFTP', 's3' => $t('remote_s3')]),
                        TextInput::make('backups_remote_keep')->label($t('keep'))->integer()->minValue(1)->maxValue(365),
                        TextInput::make('backups_remote_host')->label($t('remote_host'))->visible(fn ($get) => in_array($get('backups_remote'), ['ftp', 'sftp'], true)),
                        TextInput::make('backups_remote_port')->label($t('remote_port'))->integer()->visible(fn ($get) => in_array($get('backups_remote'), ['ftp', 'sftp'], true)),
                        TextInput::make('backups_remote_bucket')->label($t('remote_bucket'))->visible(fn ($get) => $get('backups_remote') === 's3'),
                        TextInput::make('backups_remote_region')->label($t('remote_region'))->placeholder('eu-central-1')->visible(fn ($get) => $get('backups_remote') === 's3'),
                        TextInput::make('backups_remote_endpoint')->label($t('remote_endpoint'))->helperText($t('remote_endpoint_help'))->url()->visible(fn ($get) => $get('backups_remote') === 's3'),
                        TextInput::make('backups_remote_username')->label(fn ($get) => $t($get('backups_remote') === 's3' ? 'remote_key' : 'remote_username'))->visible(fn ($get) => $get('backups_remote') !== 'none'),
                        TextInput::make('backups_remote_password')->label(fn ($get) => $t($get('backups_remote') === 's3' ? 'remote_secret' : 'remote_password'))->helperText($t('secret_keep'))
                            ->password()->revealable()->visible(fn ($get) => $get('backups_remote') !== 'none'),
                        TextInput::make('backups_remote_root')->label($t('remote_root'))->placeholder('zalohy/eshop')->visible(fn ($get) => $get('backups_remote') !== 'none'),
                    ]),
                ])
                ->action(function (array $data) use ($t): void {
                    $settings = app(SystemSettings::class);

                    // Prázdne heslo znamená „ponechať uložené“ (heslá sa do formulára nevypisujú).
                    foreach (['backups_password', 'backups_remote_password'] as $secret) {
                        if (blank($data[$secret] ?? null)) {
                            unset($data[$secret]);
                        }
                    }

                    $settings->fill([
                        ...collect($data)->only(array_keys($settings->toArray()))->all(),
                        'backups_enabled' => (bool) $data['backups_enabled'],
                        'backups_include_files' => (bool) $data['backups_include_files'],
                        'backups_keep' => (int) $data['backups_keep'],
                        'backups_remote_keep' => (int) ($data['backups_remote_keep'] ?? 14),
                        'backups_remote_port' => filled($data['backups_remote_port'] ?? null) ? (int) $data['backups_remote_port'] : null,
                    ])->save();
                    Notification::make()->success()->title($t('settings_saved'))->send();
                }),
            Action::make('testRemote')
                ->label($t('remote_test'))
                ->icon(Heroicon::OutlinedSignal)
                ->color('gray')
                ->visible(fn (): bool => app(RemoteBackups::class)->enabled())
                ->action(function () use ($t): void {
                    try {
                        app(RemoteBackups::class)->test();
                        Notification::make()->success()->title($t('remote_ok'))->send();
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title($t('remote_failed'))->body($e->getMessage())->persistent()->send();
                    }
                }),
            // Nahratie zálohy z iného hostingu (obnova na čistej inštalácii).
            Action::make('upload')
                ->label($t('upload'))
                ->icon(Heroicon::OutlinedArrowUpTray)
                ->color('gray')
                ->schema([
                    FileUpload::make('file')->label($t('file'))->disk('local')->directory('backup-uploads')
                        ->acceptedFileTypes(['application/zip', 'application/x-zip-compressed'])->maxSize(2_000_000)->required(),
                ])
                ->action(function (array $data) use ($t): void {
                    $source = storage_path('app/private/'.$data['file']);
                    File::ensureDirectoryExists(app(BackupManager::class)->directory());
                    File::move($source, app(BackupManager::class)->directory().'/eshop-'.now()->format('Y-m-d-His').'.zip');
                    Notification::make()->success()->title($t('uploaded'))->send();
                }),
            Action::make('create')
                ->label($t('create'))
                ->icon(Heroicon::OutlinedPlus)
                ->action(function () use ($t): void {
                    @set_time_limit(300);

                    try {
                        $result = app(BackupRunner::class)->run();
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title($t('failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    Notification::make()->{$result['check']['ok'] ? 'success' : 'warning'}()->title($t('created', ['name' => $result['name']]))
                        ->body($result['check']['message'].($result['remote'] === null ? '' : ' '.$t($result['remote'] ? 'remote_uploaded' : 'remote_failed')))->send();
                }),
        ];
    }
}
