<?php

namespace Modules\Catalog\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

/**
 * Stav skladu produktu práve klesol na hranicu „dochádza" alebo pod ňu.
 */
class StockLow
{
    use Dispatchable;

    public function __construct(
        public readonly Product $product,
        public readonly ?ProductVariant $variant = null,
    ) {}
}
