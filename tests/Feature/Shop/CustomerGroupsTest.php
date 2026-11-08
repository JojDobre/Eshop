<?php

namespace Tests\Feature\Shop;

use App\Core\Pricing\PriceContext;
use App\Models\CustomerGroup;
use App\Settings\TaxSettings;
use Livewire\Livewire;
use Modules\Cart\CartManager;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Resources\Products\RelationManagers\GroupPricesRelationManager;
use Modules\Catalog\Models\ProductGroupPrice;
use Modules\Catalog\Models\ProductOption;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\QuantityDiscount;
use Modules\Customers\CustomerSettings;
use Modules\Customers\Models\Customer;
use Modules\Tax\TaxCalculator;

class CustomerGroupsTest extends ShopTestCase
{
    private function enableGroups(bool $enabled = true): void
    {
        $settings = app(CustomerSettings::class);
        $settings->groups_enabled = $enabled;
        $settings->save();
        app(PriceContext::class)->forget();
    }

    private function group(string $code, array $attributes = []): CustomerGroup
    {
        return CustomerGroup::query()->create(['name' => ucfirst($code), 'code' => $code, ...$attributes]);
    }

    private function customer(?CustomerGroup $group = null, array $attributes = []): Customer
    {
        return Customer::query()->create(['name' => 'Firma s.r.o.', 'email' => 'firma@example.com', 'password' => 'tajne123', 'customer_group_id' => $group?->id, ...$attributes]);
    }

    public function test_default_group_discount_applies_only_when_enabled(): void
    {
        $product = $this->product('Káva', '10.00');
        $this->group('maloobchod', ['discount_percent' => 10, 'is_default' => true]);

        $this->get($product->url())->assertSee('10,00')->assertDontSee('9,00');

        $this->enableGroups();
        $this->get($product->url())->assertSee('9,00');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->assertSame(1800, app(CartManager::class)->summary()->lines[0]->total());
    }

    public function test_customer_group_prices_for_product_and_variants(): void
    {
        $this->enableGroups();
        $vip = $this->group('vip', ['discount_percent' => 5]);
        $product = $this->product('Tričko', '20.00');
        $option = ProductOption::query()->create(['product_id' => $product->id, 'name' => 'Veľkosť']);
        $m = $option->values()->create(['value' => 'M']);
        $l = $option->values()->create(['value' => 'L']);
        $variantM = ProductVariant::query()->create(['product_id' => $product->id, 'stock_quantity' => 5]);
        $variantM->values()->attach($m->id);
        $variantL = ProductVariant::query()->create(['product_id' => $product->id, 'stock_quantity' => 5]);
        $variantL->values()->attach($l->id);
        $variantL->setPrice('EUR', 2400);

        ProductGroupPrice::query()->create(['product_id' => $product->id, 'customer_group_id' => $vip->id, 'currency' => 'EUR', 'amount' => 1500]);
        ProductGroupPrice::query()->create(['product_id' => $product->id, 'product_variant_id' => $variantL->id, 'customer_group_id' => $vip->id, 'currency' => 'EUR', 'amount' => 1900]);

        // Bez prihlásenia (žiadna predvolená skupina) bežné ceny.
        $product = $product->fresh(['prices', 'groupPrices', 'variants.prices', 'variants.groupPrices']);
        $this->assertSame(2000, $product->priceIn());

        app(PriceContext::class)->set($vip);
        $this->assertSame(1500, $product->priceIn());
        // Variant bez vlastnej ceny preberá cenu produktu pre skupinu, variant s cenou má vlastnú cenu pre skupinu.
        $this->assertSame(1500, $variantM->fresh()->setRelation('product', $product)->priceIn());
        $this->assertSame(1900, $variantL->fresh()->setRelation('product', $product)->priceIn());
        // Ďalší produkt bez ceny pre skupinu: zľava skupiny 5 %.
        $this->assertSame(950, $this->product('Šiltovka', '10.00')->priceIn());

        app(PriceContext::class)->forget();
        $this->actingAs($this->customer($vip), 'customer');
        $this->get($product->url())->assertSee('15,00');
    }

    public function test_quantity_discounts_in_cart_and_on_product_page(): void
    {
        $this->enableGroups();
        $wholesale = $this->group('velkoobchod');
        $product = $this->product('Granule', '10.00', 100);
        QuantityDiscount::query()->create(['product_id' => $product->id, 'min_quantity' => 5, 'discount_percent' => 5]);
        QuantityDiscount::query()->create(['product_id' => $product->id, 'min_quantity' => 10, 'discount_percent' => 10]);
        QuantityDiscount::query()->create(['product_id' => $product->id, 'min_quantity' => 10, 'discount_percent' => 20, 'customer_group_id' => $wholesale->id]);

        $this->get($product->url())->assertSee('od 5 ks: −5 % ('.format_price(950).'/ks)', false)->assertDontSee('−20 %');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 4]);
        $this->assertSame(1000, app(CartManager::class)->summary()->lines[0]->unitPrice);

        $item = app(CartManager::class)->current()->items->first();
        app(CartManager::class)->update($item->id, 12);
        $this->assertSame(900, app(CartManager::class)->summary()->lines[0]->unitPrice);

        app(PriceContext::class)->set($wholesale);
        $this->assertSame(800, app(CartManager::class)->summary()->lines[0]->unitPrice);
    }

    public function test_b2b_group_sees_prices_without_vat(): void
    {
        app(TaxSettings::class)->fill(['vat_payer' => true, 'prices_include_vat' => true])->save();
        $this->enableGroups();
        $b2b = $this->group('firmy', ['prices_without_vat' => true]);
        $product = $this->product('Tlačiareň', '123.00');
        $net = TaxCalculator::netFromGross(12300, $product->vatRate());
        $this->assertLessThan(12300, $net);

        $this->actingAs($this->customer($b2b), 'customer');
        $this->get($product->url())
            ->assertSee(format_price($net))
            ->assertSee('Cena bez DPH. S DPH: '.format_price(12300), false);
        $this->get('/')->assertSee('bez DPH');
    }

    public function test_blocked_customer_cannot_log_in_or_order(): void
    {
        $customer = $this->customer(null, ['is_blocked' => true]);

        $this->post(route('customer.login'), ['email' => 'firma@example.com', 'password' => 'tajne123'])
            ->assertSessionHasErrors(['email' => __('customers::messages.blocked')]);
        $this->assertGuest('customer');

        // Ani bez prihlásenia s rovnakým e-mailom.
        $this->post(route('cart.add'), ['product_id' => $this->product('Káva')->id]);
        $this->post(route('checkout.store'), $this->checkoutData(['email' => 'Firma@Example.com']))->assertSessionHasErrors('email');

        // Zákazník zablokovaný počas prihlásenia je pri vstupe do účtu odhlásený.
        $customer->update(['is_blocked' => false]);
        $this->actingAs($customer, 'customer');
        $customer->update(['is_blocked' => true]);
        $this->get(route('customer.account'))->assertRedirect(route('customer.login'));
        $this->assertGuest('customer');
    }

    public function test_admin_manages_groups_and_group_prices(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/customer-groups')->assertForbidden();

        $this->enableGroups();
        $this->get('/admin/customer-groups')->assertOk();
        $vip = $this->group('vip', ['is_default' => true]);
        $other = $this->group('firmy', ['is_default' => true]);
        $this->assertFalse($vip->fresh()->is_default);
        $this->assertTrue($other->fresh()->is_default);

        $customer = $this->customer();
        $this->get('/admin/customers/'.$customer->id.'/edit')->assertOk()->assertSee(__('customers::admin.group'));

        $product = $this->product('Káva', '10.00');
        Livewire::test(GroupPricesRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callTableAction('create', data: ['customer_group_id' => $vip->id, 'currency' => 'EUR', 'amount' => '8,50'])
            ->assertHasNoTableActionErrors()
            ->callTableAction('create', data: ['customer_group_id' => $vip->id, 'currency' => 'EUR', 'amount' => '7'])
            ->assertHasTableActionErrors(['currency']);

        $this->assertSame(850, ProductGroupPrice::query()->sole()->amount);
    }
}
