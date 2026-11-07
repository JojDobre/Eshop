<?php

namespace Modules\Catalog\Filament\Widgets;

use App\Settings\CatalogSettings;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Catalog\Models\Product;

class LowStockProducts extends TableWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return ProductResource::canAccess();
    }

    public function table(Table $table): Table
    {
        $default = app(CatalogSettings::class)->low_stock_threshold;

        return $table
            ->heading(__('catalog::admin.low_stock.widget'))
            ->query(fn (): Builder => Product::query()
                ->where('track_stock', true)
                ->where('is_visible', true)
                ->whereRaw('stock_quantity <= COALESCE(low_stock_threshold, ?)', [$default])
                ->orderBy('stock_quantity'))
            ->columns([
                TextColumn::make('name')->label(__('catalog::admin.product.name')),
                TextColumn::make('sku')->label(__('catalog::admin.product.sku'))->placeholder('—'),
                TextColumn::make('stock_quantity')->label(__('catalog::admin.product.stock_quantity'))->badge()
                    ->color(fn (int $state) => $state > 0 ? 'warning' : 'danger'),
            ])
            ->recordUrl(fn (Product $record) => ProductResource::getUrl('edit', ['record' => $record]))
            ->emptyStateHeading(__('catalog::admin.low_stock.empty'))
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
