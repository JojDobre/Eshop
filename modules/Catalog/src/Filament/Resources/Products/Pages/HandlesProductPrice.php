<?php

namespace Modules\Catalog\Filament\Resources\Products\Pages;

use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldValues;
use Modules\Catalog\Models\Product;

/**
 * Cena a vlastné polia nie sú stĺpce produktu (ceny sú podľa meny v tabuľke product_prices),
 * preto ju formulár ukladá samostatne.
 */
trait HandlesProductPrice
{
    private ?int $pendingPrice = null;

    private ?int $pendingCompareAt = null;

    /** @var array<string, mixed>|null */
    private ?array $pendingCustomFields = null;

    /** @var array<string, array{price?: mixed, compare_at?: mixed}> */
    private array $pendingOtherPrices = [];

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractPrice(array $data): array
    {
        $currency = app(CurrencyContext::class)->current();
        $this->pendingPrice = isset($data['price']) && $data['price'] !== '' ? Price::toMinor((string) $data['price'], $currency) : null;
        $this->pendingCompareAt = isset($data['compare_at_price']) && $data['compare_at_price'] !== '' ? Price::toMinor((string) $data['compare_at_price'], $currency) : null;
        $this->pendingCustomFields = $data['custom_fields'] ?? null;
        $this->pendingOtherPrices = (array) ($data['other_prices'] ?? []);
        unset($data['price'], $data['compare_at_price'], $data['custom_fields'], $data['other_prices']);

        return $data;
    }

    private function savePrice(Product $product): void
    {
        if ($this->pendingCustomFields !== null) {
            $locale = method_exists($this, 'currentEditingLocale') ? $this->currentEditingLocale() : null;
            app(CustomFieldValues::class)->save(CustomField::PRODUCT, $product->id, $this->pendingCustomFields, $locale);
        }

        if ($this->pendingPrice !== null) {
            $product->setPrice(app(CurrencyContext::class)->current(), $this->pendingPrice, $this->pendingCompareAt);
        }

        // Ceny v ďalších menách: vyplnená = ručná cena, prázdna = prepočet kurzom.
        foreach ($this->pendingOtherPrices as $currency => $values) {
            if (! app(Currencies::class)->isEnabled($currency) || $currency === app(CurrencyContext::class)->current()) {
                continue;
            }

            $price = $values['price'] ?? null;

            if ($price === null || $price === '') {
                $product->prices()->whereNull('product_variant_id')->where('currency', $currency)->delete();
                $product->unsetRelation('prices');

                continue;
            }

            $compareAt = $values['compare_at'] ?? null;
            $product->setPrice($currency, Price::toMinor((string) $price, $currency), $compareAt === null || $compareAt === '' ? null : Price::toMinor((string) $compareAt, $currency));
        }
    }
}
