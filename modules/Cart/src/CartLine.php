<?php

namespace Modules\Cart;

use Modules\Cart\Models\CartItem;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

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
        public readonly ?ProductVariant $variant = null,
        /** @var list<array{id: int, name: string, price: int, text: string|null}> Doplnkové voľby (cena je v jednotkovej cene). */
        public readonly array $addons = [],
    ) {}

    /**
     * Názov pre zobrazenie, napr. „Tričko – M / červená".
     */
    public function name(): string
    {
        return $this->variant ? $this->product->name.' – '.$this->variant->name() : $this->product->name;
    }

    /** Doplnkové voľby na zobrazenie, napr. „Gravírovanie: Pre Janu“. */
    /** Je riadok (aspoň sčasti) predobjednávkou? */
    public function isPreorder(): bool
    {
        return $this->variant ? $this->variant->setRelation('product', $this->product)->isPreorder($this->quantity) : $this->product->isPreorder($this->quantity);
    }

    public function addonsLabel(): ?string
    {
        if ($this->addons === []) {
            return null;
        }

        return implode(', ', array_map(fn (array $addon) => $addon['text'] !== null ? $addon['name'].': '.$addon['text'] : $addon['name'], $this->addons));
    }

    public function maxQuantity(): ?int
    {
        return $this->variant?->maxPurchasableQuantity() ?? $this->product->maxPurchasableQuantity();
    }

    /**
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return $this->variant?->imageUrls() ?? $this->product->imageUrls();
    }

    public function total(): int
    {
        return ($this->unitPrice ?? 0) * $this->quantity;
    }
}
