<?php

namespace Modules\Catalog\Filament\Resources\Products\Pages;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use Modules\Catalog\Models\Product;

/**
 * Cena nie je stĺpec produktu (ceny sú podľa meny v tabuľke product_prices),
 * preto ju formulár ukladá samostatne.
 */
trait HandlesProductPrice
{
    private ?int $pendingPrice = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractPrice(array $data): array
    {
        $currency = app(CurrencyContext::class)->current();
        $this->pendingPrice = isset($data['price']) && $data['price'] !== '' ? Price::toMinor((string) $data['price'], $currency) : null;
        unset($data['price']);

        return $data;
    }

    private function savePrice(Product $product): void
    {
        if ($this->pendingPrice !== null) {
            $product->setPrice(app(CurrencyContext::class)->current(), $this->pendingPrice);
        }
    }
}
