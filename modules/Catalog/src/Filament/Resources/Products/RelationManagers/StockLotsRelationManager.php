<?php

namespace Modules\Catalog\Filament\Resources\Products\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Filament\StockLotTable;
use Modules\Catalog\Models\Product;
use Modules\Catalog\StockLots;

/**
 * Zásoby produktu po skladoch a šaržiach (príjem, inventúra, presun, odpis).
 */
class StockLotsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockLots';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('catalog::admin.lots.title');
    }

    public function mount(): void
    {
        parent::mount();

        // Doterajší sklad (pred zapnutím funkcie) sa zobrazí v predvolenom sklade.
        $product = $this->getOwnerRecord();

        if ($product instanceof Product) {
            $lots = app(StockLots::class);
            $product->hasVariants() ? $product->variants->each(fn ($variant) => $lots->reconcile($product, $variant)) : $lots->reconcile($product);
        }
    }

    public function table(Table $table): Table
    {
        /** @var Product $product */
        $product = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['warehouse', 'variant.values']))
            ->columns(StockLotTable::columns(withProduct: false))
            ->filters(StockLotTable::filters())
            ->headerActions([StockLotTable::receiveAction($product)])
            ->recordActions(StockLotTable::recordActions())
            ->defaultSort('expires_at')
            ->paginated([10, 25, 50]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Product && $ownerRecord->track_stock && ! $ownerRecord->is_bundle && app(StockLots::class)->enabled();
    }
}
