<?php

namespace Tests\Feature\Shop;

use App\Settings\CompanySettings;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\Mail\InvoiceMail;
use Modules\Orders\Models\Order;
use Modules\Orders\PaymentStatus;
use Modules\Payments\Models\PaymentMethod;
use Modules\Payments\PaymentType;
use Modules\Stripe\Filament\Pages\ManageStripeSettings;
use Modules\Stripe\Models\StripePayment;
use Modules\Stripe\StripeApi;
use Modules\Stripe\StripeSettings;
use RuntimeException;

class PaymentsTest extends ShopTestCase
{
    private FakeStripeApi $stripe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stripe = new FakeStripeApi;
        $this->app->instance(StripeApi::class, $this->stripe);
    }

    private function enableStripe(): PaymentMethod
    {
        $settings = app(StripeSettings::class);
        $settings->enabled = true;
        $settings->secret_key = 'sk_test_123';
        $settings->webhook_secret = 'whsec_test';
        $settings->save();

        $card = $this->payment(PaymentType::Card);
        $card->update(['is_active' => true]);

        return $card;
    }

    private function placeCardOrder(): Order
    {
        $card = $this->enableStripe();
        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $response = $this->post(route('checkout.store'), $this->checkoutData(['payment_method_id' => $card->id]));
        $order = Order::query()->latest('id')->firstOrFail();
        $response->assertRedirect(route('orders.pay', $order->token));

        return $order;
    }

    public function test_order_page_shows_payment_qr_for_bank_transfer(): void
    {
        $company = app(CompanySettings::class);
        $company->iban = 'SK31 1200 0000 1987 4263 7541';
        $company->save();

        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData());
        $order = Order::query()->firstOrFail();

        $this->get($order->publicUrl())->assertOk()->assertSee('data:image/', false)->assertSee('Naskenujte v aplikácii banky');

        $order->update(['payment_status' => PaymentStatus::Paid]);
        $this->get($order->publicUrl())->assertDontSee('Naskenujte v aplikácii banky');
    }

    public function test_card_payment_is_hidden_until_gateway_is_configured(): void
    {
        $card = $this->payment(PaymentType::Card);
        $card->update(['is_active' => true]);
        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->get(route('checkout.show'))->assertOk()->assertDontSee('Platba kartou');
        $this->post(route('checkout.store'), $this->checkoutData(['payment_method_id' => $card->id]))->assertSessionHasErrors('payment_method_id');

        $this->enableStripe();
        $this->get(route('checkout.show'))->assertSee('Platba kartou');
    }

    public function test_card_order_redirects_to_stripe_and_is_paid_after_return(): void
    {
        Mail::fake();
        $order = $this->placeCardOrder();

        $this->get(route('orders.pay', $order->token))->assertRedirect('https://checkout.stripe.test/cs_test_1');
        $params = $this->stripe->created[0];
        $this->assertSame($order->total, $params['line_items'][0]['price_data']['unit_amount']);
        $this->assertSame('eur', $params['line_items'][0]['price_data']['currency']);
        $this->assertDatabaseHas('stripe_payments', ['order_id' => $order->id, 'session_id' => 'cs_test_1', 'amount' => $order->total]);

        $this->get($order->publicUrl())->assertSee('Zaplatiť kartou');

        // Zákazník zrušil platbu.
        $this->get(route('stripe.cancel', $order->token))->assertRedirect($order->publicUrl());
        $this->assertFalse($order->fresh()->isPaid());

        // Neznáma relácia sa ignoruje.
        $this->stripe->sessions['cs_other'] = ['id' => 'cs_other', 'payment_status' => 'paid', 'amount_total' => $order->total, 'currency' => 'eur'];
        $this->get(route('stripe.return', $order->token).'?session_id=cs_other');
        $this->assertFalse($order->fresh()->isPaid());

        $this->stripe->sessions['cs_test_1']['payment_status'] = 'paid';
        $this->get(route('stripe.return', $order->token).'?session_id=cs_test_1')->assertRedirect($order->publicUrl());

        $order->refresh();
        $this->assertTrue($order->isPaid());
        $this->assertSame(StripePayment::PAID, StripePayment::query()->firstOrFail()->status);
        $this->assertSame(1, $order->invoices()->where('type', InvoiceType::Invoice)->count());
        Mail::assertSent(InvoiceMail::class);

        $this->followingRedirects()->get(route('stripe.return', $order->token).'?session_id=cs_test_1')->assertSee('Platba bola prijatá');
        $this->get(route('orders.pay', $order->token))->assertRedirect($order->publicUrl());
    }

    public function test_amount_mismatch_does_not_mark_order_paid(): void
    {
        $order = $this->placeCardOrder();
        $this->get(route('orders.pay', $order->token));

        $this->stripe->sessions['cs_test_1']['payment_status'] = 'paid';
        $this->stripe->sessions['cs_test_1']['amount_total'] = 1;
        $this->get(route('stripe.return', $order->token).'?session_id=cs_test_1');

        $this->assertFalse($order->fresh()->isPaid());
    }

    public function test_webhook_confirms_payment_with_valid_signature(): void
    {
        Mail::fake();
        $order = $this->placeCardOrder();
        $this->get(route('orders.pay', $order->token));

        $payload = json_encode([
            'id' => 'evt_1', 'object' => 'event', 'type' => 'checkout.session.completed',
            'data' => ['object' => ['id' => 'cs_test_1', 'object' => 'checkout.session', 'payment_status' => 'paid',
                'amount_total' => $order->total, 'currency' => 'eur', 'payment_intent' => 'pi_1']],
        ]);
        $signature = fn (string $secret) => 't='.time().',v1='.hash_hmac('sha256', time().'.'.$payload, $secret);

        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature('whsec_wrong'), 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertStatus(400);
        $this->assertFalse($order->fresh()->isPaid());

        $this->call('POST', route('stripe.webhook'), [], [], [], ['HTTP_STRIPE_SIGNATURE' => $signature('whsec_test'), 'CONTENT_TYPE' => 'application/json'], $payload)
            ->assertOk();
        $this->assertTrue($order->fresh()->isPaid());
        $this->assertSame('pi_1', StripePayment::query()->firstOrFail()->payment_intent);
    }

    public function test_gateway_error_returns_customer_to_order(): void
    {
        $order = $this->placeCardOrder();
        $this->stripe->fail = true;

        $this->get(route('orders.pay', $order->token))->assertRedirect($order->publicUrl())->assertSessionHas('payment_error');
    }

    public function test_admin_can_configure_stripe(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/stripe')->assertOk();

        Livewire::test(ManageStripeSettings::class)
            ->fillForm(['enabled' => true, 'publishable_key' => 'pk_test_abc', 'secret_key' => 'sk_test_abc', 'webhook_secret' => 'whsec_abc'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('verify')
            ->assertNotified();

        $this->assertTrue(app(StripeSettings::class)->isConfigured());
        $this->assertStringNotContainsString('sk_test_abc', (string) \DB::table('settings')->where('group', 'stripe')->where('name', 'secret_key')->value('payload'));

        Livewire::test(ManageStripeSettings::class)
            ->fillForm(['secret_key' => 'nope'])
            ->call('save')
            ->assertHasFormErrors(['secret_key']);
    }
}

/**
 * Falošné Stripe API pre testy.
 */
class FakeStripeApi implements StripeApi
{
    /** @var list<array<string, mixed>> */
    public array $created = [];

    /** @var array<string, array<string, mixed>> */
    public array $sessions = [];

    public bool $fail = false;

    public function createCheckoutSession(array $params): array
    {
        if ($this->fail) {
            throw new RuntimeException('Stripe is down.');
        }

        $this->created[] = $params;
        $id = 'cs_test_'.count($this->created);
        $this->sessions[$id] = ['id' => $id, 'url' => 'https://checkout.stripe.test/'.$id, 'payment_status' => 'unpaid',
            'amount_total' => $params['line_items'][0]['price_data']['unit_amount'], 'currency' => $params['line_items'][0]['price_data']['currency'], 'payment_intent' => null];

        return $this->sessions[$id];
    }

    public function retrieveCheckoutSession(string $id): array
    {
        return $this->sessions[$id] ?? throw new RuntimeException('No such session.');
    }

    public function verify(): string
    {
        return 'test';
    }
}
