<?php

namespace Modules\Cart;

/**
 * Obsah košíka s cenami. Dopravu a platbu pripočíta pokladňa.
 */
final class CartSummary
{
    /**
     * @param  list<CartLine>  $lines
     */
    public function __construct(
        public readonly array $lines,
        public readonly string $currency,
    ) {}

    public function subtotal(): int
    {
        return array_sum(array_map(fn (CartLine $line) => $line->total(), $this->lines));
    }

    public function itemCount(): int
    {
        return array_sum(array_map(fn (CartLine $line) => $line->quantity, $this->lines));
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    /**
     * Dá sa košík objednať? (Všetky položky sú dostupné.)
     */
    public function canCheckout(): bool
    {
        if ($this->isEmpty()) {
            return false;
        }

        foreach ($this->lines as $line) {
            if (! $line->isAvailable) {
                return false;
            }
        }

        return true;
    }

    /**
     * Celková hmotnosť v gramoch (pre výpočet dopravy podľa hmotnosti v ďalších krokoch).
     */
    public function weight(): int
    {
        return array_sum(array_map(fn (CartLine $line) => ($line->variant?->weight ?? $line->product->weight ?? 0) * $line->quantity, $this->lines));
    }

    /** Obsahuje košík tovar, ktorý treba doručiť? (Služby a digitálne produkty nie.) */
    public function requiresShipping(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->product->requiresShipping()) {
                return true;
            }
        }

        return $this->lines === [];
    }
}
