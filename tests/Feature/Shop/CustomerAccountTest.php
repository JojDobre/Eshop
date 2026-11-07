<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Modules\Customers\Filament\Resources\Customers\Pages\EditCustomer;
use Modules\Customers\Models\Customer;
use Modules\Orders\Models\Order;

class CustomerAccountTest extends ShopTestCase
{
    private function customer(array $attributes = []): Customer
    {
        return Customer::query()->create(['name' => 'Jana Nováková', 'email' => 'jana@example.com', 'password' => 'tajne123', ...$attributes]);
    }

    private function registrationData(array $overrides = []): array
    {
        return ['name' => 'Jana Nováková', 'email' => 'Jana@Example.com', 'password' => 'tajne123', 'password_confirmation' => 'tajne123', 'terms' => '1', ...$overrides];
    }

    public function test_customer_can_register_and_receives_welcome_mail(): void
    {
        Mail::fake();
        $this->get(route('customer.register'))->assertOk()->assertSee('obchodnými podmienkami', false);

        $this->post(route('customer.register'), $this->registrationData())->assertRedirect(route('customer.account'));

        $customer = Customer::query()->firstOrFail();
        $this->assertSame('jana@example.com', $customer->email);
        $this->assertNotNull($customer->terms_accepted_at);
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertGuest('web');
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'customers.welcome' && $mail->hasTo('jana@example.com'));

        $this->get(route('customer.account'))->assertOk()->assertSee('Dobrý deň, Jana Nováková');
    }

    public function test_registration_validation(): void
    {
        $this->customer();

        $this->post(route('customer.register'), $this->registrationData())->assertSessionHasErrors('email');
        $this->post(route('customer.register'), $this->registrationData(['email' => 'iny@example.com', 'terms' => null]))->assertSessionHasErrors('terms');
        $this->post(route('customer.register'), $this->registrationData(['email' => 'iny@example.com', 'password' => 'kratke', 'password_confirmation' => 'kratke']))->assertSessionHasErrors('password');
        $this->assertSame(1, Customer::query()->count());
    }

    public function test_login_logout_and_throttling(): void
    {
        $customer = $this->customer();

        $this->get(route('customer.account'))->assertRedirect(route('customer.login'));
        $this->post(route('customer.login'), ['email' => 'jana@example.com', 'password' => 'zle'])->assertSessionHasErrors('email');
        $this->assertGuest('customer');

        $this->post(route('customer.login'), ['email' => 'JANA@example.com', 'password' => 'tajne123'])->assertRedirect(route('customer.account'));
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertNotNull($customer->fresh()->last_login_at);
        $this->get(route('customer.login'))->assertRedirect(route('customer.account'));

        $this->post(route('customer.logout'))->assertRedirect(route('storefront.home'));
        $this->assertGuest('customer');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('customer.login'), ['email' => 'jana@example.com', 'password' => 'zle']);
        }
        $this->post(route('customer.login'), ['email' => 'jana@example.com', 'password' => 'tajne123'])->assertSessionHasErrors('email');
        $this->assertGuest('customer');
    }

    public function test_customer_cannot_access_admin(): void
    {
        $this->actingAs($this->customer(), 'customer');

        $this->get('/admin')->assertRedirect();
        $this->assertGuest('web');
    }

    public function test_password_reset(): void
    {
        Mail::fake();
        $customer = $this->customer();

        $this->post(route('customer.password.email'), ['email' => 'nikto@example.com'])->assertSessionHas('success');
        Mail::assertNothingSent();

        $this->post(route('customer.password.email'), ['email' => 'jana@example.com'])->assertSessionHas('success');
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'customers.password_reset'
            && str_contains($mail->variables['reset_url'], '/ucet/nove-heslo/'));

        $token = Password::broker('customers')->createToken($customer);
        $this->get(route('customer.password.reset', ['token' => $token, 'email' => $customer->email]))->assertOk();

        $this->post(route('customer.password.update'), ['token' => 'zly', 'email' => $customer->email, 'password' => 'nove1234', 'password_confirmation' => 'nove1234'])
            ->assertSessionHasErrors('email');
        $this->post(route('customer.password.update'), ['token' => $token, 'email' => $customer->email, 'password' => 'nove1234', 'password_confirmation' => 'nove1234'])
            ->assertRedirect(route('customer.login'));

        $this->post(route('customer.login'), ['email' => 'jana@example.com', 'password' => 'nove1234']);
        $this->assertAuthenticated('customer');
    }

    public function test_order_is_linked_to_account_and_addresses_are_remembered(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'customer');
        $product = $this->product('Etiópia', '12.90');

        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->get(route('checkout.show'))->assertOk()->assertSee('value="jana@example.com"', false);
        $this->post(route('checkout.store'), $this->checkoutData(['email' => 'jana@example.com', 'is_company' => '1', 'billing_company' => 'Jana s.r.o.', 'billing_company_id' => '12345678']));

        $order = Order::query()->firstOrFail();
        $this->assertSame($customer->id, $order->customer_id);
        $customer->refresh();
        $this->assertSame('Hlavná 1', $customer->billing_address['street']);
        $this->assertSame('Jana s.r.o.', $customer->billing_address['company']);
        $this->assertSame('+421 900 123 456', $customer->phone);

        // Ďalší nákup: pokladňa je predvyplnená.
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->get(route('checkout.show'))->assertSee('value="Hlavná 1"', false)->assertSee('value="Jana s.r.o."', false);

        $this->get(route('customer.account'))->assertSee($order->number);

        // Objednávka hosťa sa k účtu nepriradí.
        $this->post(route('customer.logout'));
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData(['email' => 'jana@example.com']));
        $this->assertNull(Order::query()->latest('id')->first()->customer_id);
        $this->assertSame(1, $customer->orders()->count());
    }

    public function test_customer_cannot_see_other_customers_orders(): void
    {
        $other = $this->customer(['email' => 'iny@example.com']);
        $this->actingAs($other, 'customer');
        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData());
        $order = Order::query()->firstOrFail();

        $this->actingAs($this->customer(), 'customer');
        $this->get(route('customer.account'))->assertOk()->assertDontSee($order->number);
    }

    public function test_profile_password_and_account_deletion(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer, 'customer');

        $this->get(route('customer.profile'))->assertOk();
        $this->put(route('customer.profile.update'), [
            'name' => 'Jana Malá', 'email' => 'jana.mala@example.com', 'phone' => '+421 911 111 111',
            'billing_address' => ['street' => 'Nová 5', 'city' => 'Košice', 'zip' => '040 01', 'country' => 'SK'],
            'shipping_address' => ['street' => '', 'country' => 'SK'],
        ])->assertSessionHasNoErrors();
        $customer->refresh();
        $this->assertSame('jana.mala@example.com', $customer->email);
        $this->assertSame(['street' => 'Nová 5', 'city' => 'Košice', 'zip' => '040 01', 'country' => 'SK'], $customer->billing_address);

        $this->put(route('customer.profile.password'), ['current_password' => 'zle', 'password' => 'nove1234', 'password_confirmation' => 'nove1234'])
            ->assertSessionHasErrorsIn('password', 'current_password');
        $this->put(route('customer.profile.password'), ['current_password' => 'tajne123', 'password' => 'nove1234', 'password_confirmation' => 'nove1234'])
            ->assertSessionHasNoErrors();

        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData());

        $this->delete(route('customer.destroy'), ['delete_password' => 'zle'])->assertSessionHasErrorsIn('delete', 'delete_password');
        $this->delete(route('customer.destroy'), ['delete_password' => 'nove1234'])->assertRedirect(route('storefront.home'));

        $this->assertGuest('customer');
        $this->assertSame(0, Customer::query()->count());
        $this->assertNull(Order::query()->firstOrFail()->customer_id);
    }

    public function test_admin_can_manage_customers(): void
    {
        Mail::fake();
        $customer = $this->customer();
        $this->actingAs($this->superAdmin());

        $this->get('/admin/customers')->assertOk()->assertSee('jana@example.com');
        $this->get('/admin/customers/'.$customer->id.'/edit')->assertOk();

        Livewire::test(EditCustomer::class, ['record' => $customer->id])
            ->fillForm(['name' => 'Jana Veľká', 'email' => 'JANA2@example.com'])
            ->call('save')
            ->assertHasNoFormErrors()
            ->callAction('sendReset');

        $this->assertSame('jana2@example.com', $customer->fresh()->email);
        Mail::assertSent(TemplateMail::class);

        Livewire::test(EditCustomer::class, ['record' => $customer->id])->callAction('delete');
        $this->assertSame(0, Customer::query()->count());
    }
}
