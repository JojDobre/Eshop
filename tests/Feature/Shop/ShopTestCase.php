<?php

namespace Tests\Feature\Shop;

use App\Core\Events\EshopInstalled;
use App\Core\Support\Price;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\PaymentType;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;
use Tests\Feature\InstalledTestCase;

/**
 * Nainštalovaný obchod s predvolenou dopravou, platbami a stránkami (bez ukážkového katalógu).
 */
abstract class ShopTestCase extends InstalledTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        EshopInstalled::dispatch('sk', 'EUR', 'SK', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function product(string $name, string $price = '10.00', int $stock = 10, ?Category $category = null, array $attributes = []): Product
    {
        $product = Product::query()->create(['name' => $name, 'stock_quantity' => $stock, ...$attributes]);
        $product->setPrice('EUR', Price::toMinor($price, 'EUR'));

        if ($category) {
            $product->categories()->attach($category->id);
        }

        return $product->fresh(['prices']);
    }

    protected function category(string $name, ?Category $parent = null): Category
    {
        return Category::query()->create(['name' => $name, 'parent_id' => $parent?->id]);
    }

    protected function pickup(): ShippingMethod
    {
        return ShippingMethod::query()->where('type', ShippingType::Pickup)->firstOrFail();
    }

    protected function courier(): ShippingMethod
    {
        return ShippingMethod::query()->where('type', ShippingType::Delivery)->firstOrFail();
    }

    protected function payment(PaymentType $type): PaymentMethod
    {
        return PaymentMethod::query()->where('type', $type)->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function checkoutData(array $overrides = []): array
    {
        return [
            'email' => 'Jana@Example.com',
            'phone' => '+421 900 123 456',
            'billing_name' => 'Jana Nováková',
            'billing_street' => 'Hlavná 1',
            'billing_city' => 'Bratislava',
            'billing_zip' => '811 01',
            'billing_country' => 'SK',
            'shipping_method_id' => $this->courier()->id,
            'payment_method_id' => $this->payment(PaymentType::BankTransfer)->id,
            'terms' => '1',
            ...$overrides,
        ];
    }
}
