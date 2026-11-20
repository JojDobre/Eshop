<?php

namespace Modules\Orders\Filament\Widgets;

use Filament\Widgets\Widget;
use Modules\Catalog\Filament\Resources\Products\ProductResource;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Reports\SalesReport;

/**
 * Najpredávanejšie produkty za posledných 30 dní (podľa počtu kusov).
 */
class TopProducts extends Widget
{
    protected static ?int $sort = 3;

    protected string $view = 'orders::filament.widgets.top-products';

    public static function canView(): bool
    {
        return OrderResource::canAccess();
    }

    /**
     * @return list<array{name: string, quantity: int, url: string|null, share: float}>
     */
    public function rows(): array
    {
        $rows = app(SalesReport::class)->topProducts(30);
        $max = max(1, ...array_column($rows, 'quantity') ?: [1]);

        return array_map(fn (array $row) => [
            'name' => $row['name'],
            'quantity' => $row['quantity'],
            'url' => class_exists(ProductResource::class) && $row['product_id'] ? ProductResource::getUrl('edit', ['record' => $row['product_id']]) : null,
            'share' => $row['quantity'] / $max,
        ], $rows);
    }
}
