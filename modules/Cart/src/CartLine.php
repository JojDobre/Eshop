<?php

namespace Modules\Cart;

use Modules\Cart\Models\CartItem;
use Modules\Catalog\Models\Product;

/**
 * Jeden riadok košíka s vypočítanou cenou.
 */
final class CartLine
{
    public function __construct(
        public readonly CartItem $item,
        public readonly Product $product,
        public readonly int $quantity,
        /** Jednotková cena (null = produkt nemá cenu v tejto mene). */
        public readonly ?int $unitPrice,
        /** Dá sa riadok objednať (produkt je zobrazený, má cenu a dosť kusov na sklade)? */
        public readonly bool $isAvailable,
    ) {}

    public function total(): int
    {
        return ($this->unitPrice ?? 0) * $this->quantity;
    }
}
