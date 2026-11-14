<?php

namespace App\Filament\Pages\System;

use App\Core\System\LogReader;
use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Number;
use UnitEnum;

/**
 * Prehliadač chýb a udalostí z logov (bez prístupu na FTP).
 */
class Logs extends Page implements HasTable
{
    use InteractsWithTable;

    private const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentMagnifyingGlass;

    protected static ?int $navigationSort = 92;

    protected static ?string $slug = 'system/logs';

    protected string $view = 'filament.pages.system.table';

    public static function getNavigationLabel(): string
    {
        return __('admin.logs.title');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::System;
    }

    public function getTitle(): string
    {
        return __('admin.logs.title');
    }

    public function getSubheading(): ?string
    {
        return __('admin.logs.intro', ['size' => Number::fileSize(app(LogReader::class)->totalSize(), 1)]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage') ?? false;
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('admin.logs.'.$key);

        return $table
            ->records(fn (array $filters) => collect(app(LogReader::class)->entries(level: $filters['level']['value'] ?? null))->keyBy('id')->all())
            ->columns([
                TextColumn::make('date')->label($t('date'))->dateTime('j. n. Y H:i:s'),
                TextColumn::make('level')->label($t('level'))->badge()
                    ->color(fn (string $state) => match ($state) {
                        'emergency', 'alert', 'critical', 'error' => 'danger',
                        'warning' => 'warning',
                        'info', 'notice' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('message')->label($t('message'))->wrap(),
            ])
            ->filters([
                SelectFilter::make('level')->label($t('level'))->options(array_combine(self::LEVELS, self::LEVELS)),
            ])
            ->recordActions([
                Action::make('detail')->label($t('detail'))->icon(Heroicon::OutlinedEye)
                    ->modalHeading(fn (array $record) => strtoupper($record['level']).' · '.$record['date']?->format('j. n. Y H:i:s'))
                    ->modalContent(fn (array $record): View => view('filament.pages.system.log-detail', ['details' => $record['details']]))
                    ->modalSubmitAction(false)
                    ->modalWidth('5xl'),
            ])
            ->emptyStateHeading($t('empty'))
            ->paginated([25, 50, 100]);
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key) => __('admin.logs.'.$key);

        return [
            Action::make('clear')
                ->label($t('clear'))
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () use ($t): void {
                    app(LogReader::class)->clear();
                    Notification::make()->success()->title($t('cleared'))->send();
                }),
        ];
    }

    /** V menu pod položkou „SystemStatusPage“ (zobrazí sa po jej otvorení), aby bolo hlavné menu krátke. */
    public static function getNavigationParentItem(): ?string
    {
        return SystemStatusPage::getNavigationLabel();
    }
}
