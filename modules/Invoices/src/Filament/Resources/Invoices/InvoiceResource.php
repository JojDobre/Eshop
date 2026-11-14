<?php

namespace Modules\Invoices\Filament\Resources\Invoices;

use App\Filament\AdminGroup;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Invoices\Filament\Resources\Invoices\Pages\ListInvoices;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use UnitEnum;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    // Slovenčina a čeština nepíšu názvy veľkými začiatočnými písmenami každého slova.
    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'invoices';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Sales;
    }

    public static function getModelLabel(): string
    {
        return __('invoices::admin.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('invoices::admin.plural');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('invoices.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        $t = fn (string $key) => __('invoices::admin.'.$key);

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('order'))
            ->columns([
                TextColumn::make('number')->label($t('number'))->searchable()->weight('medium'),
                TextColumn::make('type')->label($t('type'))->badge(),
                TextColumn::make('issued_at')->label($t('issued_at'))->date()->sortable(),
                TextColumn::make('customer.name')->label($t('customer'))
                    ->state(fn (Invoice $record) => $record->customer['company'] ?: $record->customer['name'])
                    ->description(fn (Invoice $record) => $record->customer['order_number'] ?? null),
                TextColumn::make('total')->label($t('total'))->alignEnd()
                    ->formatStateUsing(fn (Invoice $record) => format_price($record->total, $record->currency)),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('type')->label($t('type'))->options(InvoiceType::class),
                Filter::make('this_month')->label($t('this_month'))->query(fn (Builder $query) => $query->where('issued_at', '>=', now()->startOfMonth())),
            ])
            ->recordActions([
                Action::make('download')->label($t('download'))->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (Invoice $record) => route('invoices.admin', $record), shouldOpenInNewTab: true),
                Action::make('order')->label($t('order'))->icon(Heroicon::OutlinedShoppingBag)->color('gray')
                    ->visible(fn (Invoice $record) => $record->order_id !== null)
                    ->url(fn (Invoice $record) => OrderResource::getUrl('view', ['record' => $record->order_id])),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListInvoices::route('/')];
    }

    /** V menu pod položkou „OrderResource“ (zobrazí sa po jej otvorení), aby bolo hlavné menu krátke. */
    public static function getNavigationParentItem(): ?string
    {
        // Ak modul s nadradenou položkou nie je zapnutý alebo prístupný, položka ostane samostatná.
        return class_exists(OrderResource::class) && OrderResource::canAccess() ? OrderResource::getNavigationLabel() : null;
    }
}
