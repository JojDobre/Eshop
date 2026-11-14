<?php

namespace Modules\Returns\Filament\Resources\ReturnRequests;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Returns\Filament\Resources\ReturnRequests\Pages\ListReturnRequests;
use Modules\Returns\Filament\Resources\ReturnRequests\Pages\ViewReturnRequest;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\ReturnStatus;
use Modules\Returns\ReturnType;
use UnitEnum;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnLeft;

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'returns';

    protected static ?string $recordTitleAttribute = 'number';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getModelLabel(): string
    {
        return __('returns::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('returns::admin.plural');
    }

    public static function canAccess(): bool
    {
        return (bool) (auth()->user()?->can('returns.manage') || auth()->user()?->can('orders.manage'));
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Počet otvorených žiadostí v menu. */
    public static function getNavigationBadge(): ?string
    {
        $open = ReturnRequest::query()->whereNotIn('status', [ReturnStatus::Rejected, ReturnStatus::Completed])->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return ReturnRequest::query()->whereNotIn('status', [ReturnStatus::Rejected, ReturnStatus::Completed])->where('deadline_at', '<', now()->addDays(3))->exists()
            ? 'danger' : 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        $t = fn (string $key) => __('returns::admin.'.$key);

        return $schema->columns(3)->components([
            Section::make()->columnSpan(2)->columns(2)->schema([
                TextEntry::make('type')->label($t('type'))->badge(),
                TextEntry::make('status')->label($t('status'))->badge(),
                TextEntry::make('order.number')->label($t('order'))
                    ->url(fn (ReturnRequest $record) => OrderResource::getUrl('view', ['record' => $record->order_id])),
                TextEntry::make('order.billing_name')->label($t('customer'))
                    ->state(fn (ReturnRequest $record) => $record->order->billing_name.' · '.$record->order->email),
                TextEntry::make('items')->label($t('items'))->state(fn (ReturnRequest $record) => $record->itemsSummary())->columnSpanFull(),
                TextEntry::make('reason')->label($t('reason'))->placeholder('—')->columnSpanFull(),
                TextEntry::make('preferred_resolution')->label($t('preferred_resolution'))->placeholder('—'),
                TextEntry::make('refund_account')->label($t('refund_account'))->placeholder('—')->copyable(),
                TextEntry::make('resolution')->label($t('resolution'))->placeholder('—')->columnSpanFull(),
            ]),
            Section::make()->columnSpan(1)->schema([
                TextEntry::make('created_at')->label($t('created_at'))->dateTime(),
                TextEntry::make('deadline_at')->label($t('deadline_at'))->date()
                    ->color(fn (ReturnRequest $record) => $record->isOverdue() ? 'danger' : ($record->status->isClosed() ? null : 'warning'))
                    ->helperText(fn (ReturnRequest $record) => $record->status->isClosed() ? null : trans_choice('returns::admin.days_left', $record->daysLeft(), ['count' => $record->daysLeft()])),
                TextEntry::make('resolved_at')->label($t('resolved_at'))->dateTime()->placeholder('—'),
                TextEntry::make('deadline_help')->hiddenLabel()->state(fn (ReturnRequest $record) => __('returns::admin.deadline_help.'.$record->type->value))->color('gray'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('returns::admin.'.$key);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('order'))
            ->columns([
                TextColumn::make('number')->label($t('number'))->searchable()->weight('medium'),
                TextColumn::make('type')->label($t('type'))->badge(),
                TextColumn::make('order.number')->label($t('order'))->searchable(),
                TextColumn::make('order.billing_name')->label($t('customer'))->searchable(),
                TextColumn::make('status')->label($t('status'))->badge(),
                TextColumn::make('deadline_at')->label($t('deadline_at'))->date()->sortable()
                    ->color(fn (ReturnRequest $record) => $record->isOverdue() ? 'danger' : null),
                TextColumn::make('created_at')->label($t('created_at'))->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label($t('status'))->options(ReturnStatus::class),
                SelectFilter::make('type')->label($t('type'))->options(ReturnType::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReturnRequests::route('/'),
            'view' => ViewReturnRequest::route('/{record}'),
        ];
    }

    /** V menu pod položkou „OrderResource“ (zobrazí sa po jej otvorení), aby bolo hlavné menu krátke. */
    public static function getNavigationParentItem(): ?string
    {
        // Ak modul s nadradenou položkou nie je zapnutý alebo prístupný, položka ostane samostatná.
        return class_exists(OrderResource::class) && OrderResource::canAccess() ? OrderResource::getNavigationLabel() : null;
    }
}
