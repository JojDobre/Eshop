<?php

namespace App\Filament\Pages\System;

use App\Core\Backup\BackupManager;
use App\Filament\AdminGroup;
use App\Settings\SystemSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
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
        return __('admin.backups.intro');
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
                ->fillForm(fn () => [
                    'backups_enabled' => app(SystemSettings::class)->backups_enabled,
                    'backups_keep' => app(SystemSettings::class)->backups_keep,
                    'backups_include_files' => app(SystemSettings::class)->backups_include_files,
                ])
                ->schema([
                    Toggle::make('backups_enabled')->label($t('enabled'))->helperText($t('enabled_help')),
                    Toggle::make('backups_include_files')->label($t('include_files')),
                    TextInput::make('backups_keep')->label($t('keep'))->integer()->minValue(1)->maxValue(60)->required(),
                ])
                ->action(function (array $data) use ($t): void {
                    $settings = app(SystemSettings::class);
                    $settings->fill([
                        'backups_enabled' => (bool) $data['backups_enabled'],
                        'backups_include_files' => (bool) $data['backups_include_files'],
                        'backups_keep' => (int) $data['backups_keep'],
                    ])->save();
                    Notification::make()->success()->title($t('settings_saved'))->send();
                }),
            Action::make('create')
                ->label($t('create'))
                ->icon(Heroicon::OutlinedPlus)
                ->action(function () use ($t): void {
                    @set_time_limit(300);

                    try {
                        $name = app(BackupManager::class)->create(app(SystemSettings::class)->backups_include_files);
                    } catch (Throwable $e) {
                        report($e);
                        Notification::make()->danger()->title($t('failed'))->body($e->getMessage())->persistent()->send();

                        return;
                    }

                    Notification::make()->success()->title($t('created', ['name' => $name]))->send();
                }),
        ];
    }
}
