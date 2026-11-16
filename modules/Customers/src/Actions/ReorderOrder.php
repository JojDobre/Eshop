<?php

namespace Modules\Customers\Actions;

use Modules\Cart\CartException;
use Modules\Cart\CartManager;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Orders\Models\Order;

/**
 * Zopakovanie objednávky: položky starej objednávky sa pridajú do košíka v aktuálnych cenách.
 * Produkty, ktoré už nie sú v ponuke alebo na sklade, sa preskočia. Doplnkové voľby sa nepreberajú
 * (v objednávke sú uložené len názvom), zákazník si ich vyberie znova.
 */
class ReorderOrder
{
    public function __construct(private readonly CartManager $cart) {}

    /**
     * @return array{added: int, skipped: list<string>}
     */
    public function handle(Order $order): array
    {
        $added = 0;
        $skipped = [];

        foreach ($order->items()->get() as $item) {
            $product = $item->product_id ? Product::query()->visible()->with(['prices', 'variants'])->find($item->product_id) : null;
            $variant = $product && $item->product_variant_id
                ? ProductVariant::query()->with(['prices', 'values'])->where('product_id', $product->id)->find($item->product_variant_id)
                : null;

            if ($product === null || ($item->product_variant_id && $variant === null)) {
                $skipped[] = $item->variant_name ? $item->name.' – '.$item->variant_name : (string) $item->name;

                continue;
            }

            try {
                $this->cart->add($product, max(1, (int) $item->quantity), $variant);
                $added++;
            } catch (CartException) {
                $skipped[] = $item->variant_name ? $item->name.' – '.$item->variant_name : (string) $item->name;
            }
        }

        return ['added' => $added, 'skipped' => $skipped];
    }
}
