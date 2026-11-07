<?php

namespace Modules\Catalog\Filament;

use App\Models\User;
use App\Settings\CatalogSettings;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\StockLot;
use Modules\Catalog\Models\StockMovement;
use Modules\Catalog\Models\Warehouse;
use Modules\Catalog\StockLots;

/**
 * Stĺpce, filtre a akcie zásob po skladoch a šaržiach (pri produkte aj v prehľade šarží).
 */
final class StockLotTable
{
    private static function t(string $key): string
    {
        return __('catalog::admin.lots.'.$key);
    }

    /**
     * @return array<int, mixed>
     */
    public static function columns(bool $withProduct): array
    {
        $warningDays = (int) rescue(fn () => app(CatalogSettings::class)->expiration_warning_days, 0, false);

        return array_values(array_filter([
            $withProduct ? TextColumn::make('product')->label(self::t('product'))
                ->state(fn (StockLot $record) => $record->productName())
                ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('product', fn ($product) => $product->where('name', 'like', '%'.$search.'%')))
                : null,
            $withProduct ? null : TextColumn::make('variant')->label(__('catalog::admin.variants.variant'))->placeholder('—')
                ->state(fn (StockLot $record) => $record->variant?->name()),
            TextColumn::make('warehouse.name')->label(self::t('warehouse'))
                ->visible(fn () => CatalogFeatures::enabled('warehouses')),
            TextColumn::make('batch_number')->label(self::t('batch_number'))->placeholder('—')->searchable()
                ->visible(fn () => CatalogFeatures::enabled('batches')),
            TextColumn::make('produced_at')->label(self::t('produced_at'))->date()->placeholder('—')->toggleable(isToggledHiddenByDefault: true)
                ->visible(fn () => CatalogFeatures::enabled('batches')),
            TextColumn::make('expires_at')->label(self::t('expires_at'))->date()->placeholder('—')->sortable()
                ->badge()
                ->color(fn (StockLot $record) => match (true) {
                    $record->expires_at === null => 'gray',
                    $record->isExpired() => 'danger',
                    $record->daysToExpiry() <= $warningDays => 'warning',
                    default => 'success',
                })
                ->description(fn (StockLot $record) => $record->isExpired() ? self::t('expired') : null)
                ->visible(fn () => CatalogFeatures::enabled('batches')),
            TextColumn::make('quantity')->label(self::t('quantity'))->sortable(),
        ]));
    }

    /**
     * @return array<int, mixed>
     */
    public static function filters(): array
    {
        $warningDays = (int) rescue(fn () => app(CatalogSettings::class)->expiration_warning_days, 0, false);

        return [
            Filter::make('in_stock')->label(self::t('only_in_stock'))->default()
                ->query(fn (Builder $query) => $query->where('quantity', '!=', 0)),
            Filter::make('expiring')->label(self::t('expiring'))
                ->query(fn (Builder $query) => $query->whereNotNull('expires_at')->whereDate('expires_at', '<=', today()->addDays($warningDays)))
                ->visible(fn () => CatalogFeatures::enabled('batches')),
            Filter::make('expired')->label(self::t('expired'))
                ->query(fn (Builder $query) => $query->whereDate('expires_at', '<', today()))
                ->visible(fn () => CatalogFeatures::enabled('batches')),
            SelectFilter::make('warehouse_id')->label(self::t('warehouse'))->relationship('warehouse', 'name')
                ->getOptionLabelFromRecordUsing(fn (Warehouse $record) => $record->name)
                ->visible(fn () => CatalogFeatures::enabled('warehouses')),
        ];
    }

    /**
     * Príjem tovaru. Pri produkte je produkt daný, v prehľade šarží sa vyberá.
     */
    public static function receiveAction(?Product $product = null): Action
    {
        return Action::make('receive')
            ->label(self::t('receive'))
            ->icon('heroicon-o-arrow-down-tray')
            ->modalSubmitActionLabel(self::t('receive'))
            ->schema([
                Select::make('product_id')->label(self::t('product'))
                    ->options(fn () => Product::query()->where('track_stock', true)->where('is_bundle', false)->orderBy('id')->get()->mapWithKeys(fn (Product $item) => [$item->id => $item->name])->all())
                    ->searchable()->required()->live()
                    ->visible($product === null),
                Select::make('product_variant_id')->label(__('catalog::admin.variants.variant'))
                    ->options(fn (Get $get) => ProductVariant::query()->where('product_id', $product?->id ?? $get('product_id'))->get()->mapWithKeys(fn (ProductVariant $variant) => [$variant->id => $variant->name()])->all())
                    ->visible(fn (Get $get) => ProductVariant::query()->where('product_id', $product?->id ?? $get('product_id'))->exists())
                    ->required(fn (Get $get) => ProductVariant::query()->where('product_id', $product?->id ?? $get('product_id'))->exists()),
                Select::make('warehouse_id')->label(self::t('warehouse'))
                    ->options(fn () => Warehouse::query()->ordered()->get()->mapWithKeys(fn (Warehouse $warehouse) => [$warehouse->id => $warehouse->name])->all())
                    ->default(fn () => Warehouse::default()->id)->required()->selectablePlaceholder(false)
                    ->visible(fn () => CatalogFeatures::enabled('warehouses')),
                TextInput::make('batch_number')->label(self::t('batch_number'))->maxLength(64)
                    ->visible(fn () => CatalogFeatures::enabled('batches')),
                DatePicker::make('produced_at')->label(self::t('produced_at'))
                    ->visible(fn () => CatalogFeatures::enabled('batches')),
                DatePicker::make('expires_at')->label(self::t('expires_at'))->helperText(self::t('expires_at_help'))
                    ->visible(fn () => CatalogFeatures::enabled('batches')),
                TextInput::make('quantity')->label(self::t('quantity'))->integer()->minValue(1)->required(),
                TextInput::make('note')->label(__('catalog::admin.stock.note'))->maxLength(255),
            ])
            ->action(function (array $data) use ($product): void {
                $target = $product ?? Product::query()->findOrFail($data['product_id']);
                $variant = filled($data['product_variant_id'] ?? null) ? ProductVariant::query()->findOrFail($data['product_variant_id'])->setRelation('product', $target) : null;
                $warehouse = filled($data['warehouse_id'] ?? null) ? Warehouse::query()->findOrFail($data['warehouse_id']) : Warehouse::default();

                DB::transaction(fn () => app(StockLots::class)->receive(
                    $target, $variant, $warehouse, (int) $data['quantity'],
                    $data['batch_number'] ?? null, $data['produced_at'] ?? null, $data['expires_at'] ?? null,
                    $data['note'] ?? null, self::user(),
                ));

                Notification::make()->success()->title(self::t('received'))->send();
            });
    }

    /**
     * @return array<int, Action>
     */
    public static function recordActions(): array
    {
        return [
            Action::make('adjust')->label(self::t('adjust'))->icon('heroicon-o-pencil-square')
                ->schema([
                    TextInput::make('quantity')->label(self::t('counted'))->helperText(self::t('counted_help'))->integer()->minValue(0)->required()
                        ->default(fn (StockLot $record) => $record->quantity),
                    TextInput::make('note')->label(__('catalog::admin.stock.note'))->maxLength(255),
                ])
                ->action(function (StockLot $record, array $data): void {
                    DB::transaction(fn () => app(StockLots::class)->adjustLot($record, (int) $data['quantity'] - $record->quantity, StockMovement::MANUAL, $record->batch_number, $data['note'] ?? null, self::user()));
                    Notification::make()->success()->title(__('catalog::admin.stock.adjusted'))->send();
                }),
            Action::make('details')->label(self::t('details'))->icon('heroicon-o-tag')
                ->visible(fn () => CatalogFeatures::enabled('batches'))
                ->fillForm(fn (StockLot $record) => ['batch_number' => $record->batch_number, 'produced_at' => $record->produced_at, 'expires_at' => $record->expires_at])
                ->schema([
                    TextInput::make('batch_number')->label(self::t('batch_number'))->maxLength(64),
                    DatePicker::make('produced_at')->label(self::t('produced_at')),
                    DatePicker::make('expires_at')->label(self::t('expires_at')),
                ])
                ->action(fn (StockLot $record, array $data) => $record->update([
                    'batch_number' => filled($data['batch_number']) ? $data['batch_number'] : null,
                    'produced_at' => $data['produced_at'] ?: null,
                    'expires_at' => $data['expires_at'] ?: null,
                ])),
            Action::make('transfer')->label(self::t('transfer'))->icon('heroicon-o-arrows-right-left')
                ->visible(fn (StockLot $record) => CatalogFeatures::enabled('warehouses') && $record->quantity > 0 && Warehouse::query()->count() > 1)
                ->schema([
                    Select::make('warehouse_id')->label(self::t('target_warehouse'))
                        ->options(fn (StockLot $record) => Warehouse::query()->ordered()->whereKeyNot($record->warehouse_id)->get()->mapWithKeys(fn (Warehouse $warehouse) => [$warehouse->id => $warehouse->name])->all())
                        ->required(),
                    TextInput::make('quantity')->label(self::t('quantity'))->integer()->minValue(1)
                        ->maxValue(fn (StockLot $record) => $record->quantity)->default(fn (StockLot $record) => $record->quantity)->required(),
                ])
                ->action(function (StockLot $record, array $data): void {
                    DB::transaction(fn () => app(StockLots::class)->transfer($record, Warehouse::query()->findOrFail($data['warehouse_id']), (int) $data['quantity'], self::user()));
                    Notification::make()->success()->title(self::t('transferred'))->send();
                }),
            Action::make('writeOff')->label(self::t('write_off'))->icon('heroicon-o-trash')->color('danger')
                ->visible(fn (StockLot $record) => $record->quantity > 0)
                ->requiresConfirmation()
                ->modalDescription(self::t('write_off_help'))
                ->schema([TextInput::make('note')->label(__('catalog::admin.stock.note'))->maxLength(255)])
                ->action(function (StockLot $record, array $data): void {
                    DB::transaction(fn () => app(StockLots::class)->writeOff($record, note: $data['note'] ?? null, user: self::user()));
                    Notification::make()->success()->title(self::t('written_off'))->send();
                }),
        ];
    }

    public static function writeOffBulkAction(): BulkAction
    {
        return BulkAction::make('writeOff')->label(self::t('write_off'))->icon('heroicon-o-trash')->color('danger')
            ->requiresConfirmation()->modalDescription(self::t('write_off_help'))
            ->action(function (Collection $records): void {
                DB::transaction(fn () => $records->each(fn (StockLot $lot) => app(StockLots::class)->writeOff($lot, user: self::user())));
                Notification::make()->success()->title(self::t('written_off'))->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    private static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }
}
