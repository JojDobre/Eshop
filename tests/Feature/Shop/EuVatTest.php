<?php

namespace Tests\Feature\Shop;

use App\Settings\TaxSettings;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Orders\Models\Order;
use Modules\Payments\PaymentType;
use Modules\Tax\Actions\FillEuRates;
use Modules\Tax\Filament\Pages\ManageTaxSettings;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\Vies\ViesClient;

class EuVatTest extends ShopTestCase
{
    private function taxSettings(array $values): void
    {
        $settings = app(TaxSettings::class);
        $settings->fill($values)->save();
    }

    private function orderTo(string $country, array $overrides = []): Order
    {
        $product = $this->product('Etiópia', '12.30');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData(['billing_country' => $country, ...$overrides]));

        return Order::query()->latest('id')->firstOrFail();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->taxSettings(['vat_payer' => true, 'prices_include_vat' => true]);
        FillEuRates::run();
    }

    public function test_eu_rates_are_prefilled(): void
    {
        $standard = TaxRate::default();
        $this->assertSame(21.0, $standard->rateFor('CZ', 'SK'));
        $this->assertSame(19.0, $standard->rateFor('DE', 'SK'));
        $this->assertSame((float) $standard->rate, $standard->rateFor('SK', 'SK'));
    }

    public function test_domestic_vat_without_oss(): void
    {
        $order = $this->orderTo('CZ');

        $this->assertSame('domestic', $order->tax_mode);
        $this->assertSame((float) TaxRate::default()->rate, (float) $order->items->first()->tax_rate);
        $this->assertSame(1230, $order->subtotal);
    }

    public function test_oss_uses_destination_rate_and_keeps_gross_price(): void
    {
        $this->taxSettings(['oss_enabled' => true, 'oss_keep_gross_prices' => true]);
        $order = $this->orderTo('CZ');

        $this->assertSame('oss', $order->tax_mode);
        $this->assertSame('CZ', $order->tax_country);
        $this->assertSame(21.0, (float) $order->items->first()->tax_rate);
        $this->assertSame(21.0, (float) $order->shipping_tax_rate);
        $this->assertSame(1230, $order->subtotal);
        $this->assertSame([21.0], array_map(fn (array $row) => (float) $row['rate'], array_values($order->vatBreakdown())));
    }

    public function test_oss_can_recalculate_price_from_net(): void
    {
        $this->taxSettings(['oss_enabled' => true, 'oss_keep_gross_prices' => false]);
        $home = (float) TaxRate::default()->rate;
        $order = $this->orderTo('DE');

        $this->assertSame((int) round(1230 / (1 + $home / 100) * 1.19), $order->subtotal);
    }

    public function test_store_pickup_is_always_domestic(): void
    {
        $this->taxSettings(['oss_enabled' => true]);
        $order = $this->orderTo('CZ', ['shipping_method_id' => $this->pickup()->id, 'payment_method_id' => $this->payment(PaymentType::Cash)->id]);

        $this->assertSame('domestic', $order->tax_mode);
    }

    public function test_reverse_charge_for_verified_eu_business(): void
    {
        Http::fake([ViesClient::URL => Http::response(['valid' => true])]);
        $home = (float) TaxRate::default()->rate;

        $order = $this->orderTo('CZ', ['is_company' => '1', 'billing_company' => 'Firma s.r.o.', 'billing_company_id' => '12345678', 'billing_vat_id' => 'CZ12345678']);

        $this->assertTrue($order->isReverseCharge());
        $this->assertSame(0.0, (float) $order->items->first()->tax_rate);
        $this->assertSame((int) round(1230 / (1 + $home / 100)), $order->subtotal);
        $this->get($order->publicUrl())->assertSee('prenesenie daňovej povinnosti');

        $invoice = app(IssueInvoice::class)->forOrder($order, InvoiceType::Invoice);
        $this->assertStringContainsString('Prenesenie daňovej povinnosti', (string) $invoice->note);

        Http::assertSent(fn ($request) => $request['countryCode'] === 'CZ' && $request['vatNumber'] === '12345678');
    }

    public function test_invalid_or_unavailable_vies_charges_vat(): void
    {
        Http::fake([ViesClient::URL => Http::response(['valid' => false])]);
        $order = $this->orderTo('CZ', ['is_company' => '1', 'billing_company' => 'Firma', 'billing_company_id' => '1', 'billing_vat_id' => 'CZ99999999']);
        $this->assertFalse($order->isReverseCharge());

        Http::fake([ViesClient::URL => Http::response('', 503)]);
        $order = $this->orderTo('AT', ['is_company' => '1', 'billing_company' => 'Firma', 'billing_company_id' => '1', 'billing_vat_id' => 'ATU12345678']);
        $this->assertFalse($order->isReverseCharge());
    }

    public function test_admin_tax_settings(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageTaxSettings::class)
            ->fillForm(['oss_enabled' => true, 'oss_keep_gross_prices' => false, 'reverse_charge_enabled' => false])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('fillEuRates')
            ->assertNotified();

        $this->assertTrue(app(TaxSettings::class)->oss_enabled);
        $this->get('/admin/settings/tax-rates')->assertOk();
    }
}
