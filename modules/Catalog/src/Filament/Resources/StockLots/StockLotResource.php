<?php

namespace Modules\Catalog\Filament\Resources\StockLots;

use App\Filament\AdminGroup;
use App\Settings\CatalogSettings;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\Filament\Resources\StockLots\Pages\ListStockLots;
use Modules\Catalog\Filament\StockLotTable;
use Modules\Catalog\Models\StockLot;
use UnitEnum;

/**
 * Prehľad zásob všetkých produktov po skladoch a šaržiach – aj s blížiacou sa expiráciou.
 */
class StockLotResource extends Resource
{
    protected static ?string $model = StockLot::class;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?int $navigationSort = 41;

    protected static ?string $slug = 'stock';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return AdminGroup::Catalog;
    }

    public static function getModelLabel(): string
    {
        return __('catalog::admin.lots.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('catalog::admin.lots.plural');
    }

    public static function canAccess(): bool
    {
        return (CatalogFeatures::enabled('warehouses') || CatalogFeatures::enabled('batches')) && (auth()->user()?->can('catalog.manage') ?? false);
    }

    /** Počet šarží po expirácii alebo v lehote upozornenia (iba pri sledovaní expirácie). */
    public static function getNavigationBadge(): ?string
    {
        if (! CatalogFeatures::enabled('expiration')) {
            return null;
        }

        $days = (int) rescue(fn () => app(CatalogSettings::class)->expiration_warning_days, 0, false);
        $count = StockLot::query()->where('quantity', '>', 0)->whereNotNull('expires_at')->whereDate('expires_at', '<=', today()->addDays($days))->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['warehouse', 'product', 'variant.values']))
            ->columns(StockLotTable::columns(withProduct: true))
            ->filters(StockLotTable::filters())
            ->recordActions(StockLotTable::recordActions())
            ->toolbarActions([BulkActionGroup::make([StockLotTable::writeOffBulkAction()])])
            ->defaultSort('expires_at')
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return ['index' => ListStockLots::route('/')];
    }
}
