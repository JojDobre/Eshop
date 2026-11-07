<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Orders\Mail\OrderConfirmation;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Pages\Models\Page;
use Modules\Returns\Filament\Resources\ReturnRequests\Pages\ViewReturnRequest;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\ReturnStatus;
use Modules\Returns\ReturnType;
use Modules\Storefront\Cookies\CookieSettings;
use Modules\Storefront\Filament\Pages\ManageCookieSettings;

class LegalTest extends ShopTestCase
{
    private function placeOrder(int $quantity = 2): Order
    {
        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $quantity]);
        $this->post(route('checkout.store'), $this->checkoutData());

        return Order::query()->latest('id')->firstOrFail();
    }

    private function terms(): Page
    {
        return Page::query()->where('role', Page::ROLE_TERMS)->firstOrFail();
    }

    public function test_terms_are_versioned_and_consent_is_recorded_with_order(): void
    {
        $terms = $this->terms();
        $this->assertSame(1, $terms->currentVersion()->version);

        $terms->update(['position' => 5]);
        $this->assertSame(1, $terms->versions()->count());

        $terms->update(['content' => '<p>Nové znenie podmienok.</p>']);
        $this->assertSame(2, $terms->currentVersion()->version);

        $order = $this->placeOrder();
        $consent = collect($order->legal_consents)->firstWhere('document', 'terms');
        $this->assertSame(2, $consent['version']);
        $this->assertNotNull(collect($order->legal_consents)->firstWhere('document', 'privacy'));

        // Neskoršia zmena podmienok nemení, s čím zákazník súhlasil.
        $terms->update(['content' => '<p>Ešte novšie znenie.</p>']);
        $this->get($consent['url'])->assertOk()->assertSee('Nové znenie podmienok.')->assertSee('Archívna verzia 2');
        $this->get($order->publicUrl())->assertSee($consent['url'], false);
        $this->get(route('pages.version', [$terms->slug, 99]))->assertNotFound();
    }

    public function test_confirmation_has_terms_pdf_attached(): void
    {
        $this->terms()->update(['content' => '<h2>Článok 1</h2><p>Podmienky.</p>']);
        $order = $this->placeOrder();

        $attachments = collect((new OrderConfirmation($order))->attachments());
        $pdf = $attachments->first(fn ($attachment) => str_contains($attachment->as, '-v2.pdf'));
        $this->assertNotNull($pdf);
    }

    public function test_cookie_bar_and_consent_codes(): void
    {
        $this->get(route('storefront.home'))->assertOk()->assertDontSee('cookie-title');

        $settings = app(CookieSettings::class);
        $settings->analytics_code = '<script>window.analyticsLoaded = true;</script>';
        $settings->save();

        $this->get(route('storefront.home'))->assertSee('cookie-title')->assertDontSee('window.analyticsLoaded', false);

        $this->withUnencryptedCookie('eshop_consent', json_encode(['analytics' => false, 'marketing' => false]))
            ->get(route('storefront.home'))->assertDontSee('window.analyticsLoaded', false);

        $this->withUnencryptedCookie('eshop_consent', json_encode(['analytics' => true, 'marketing' => false]))
            ->get(route('storefront.home'))->assertSee('window.analyticsLoaded', false);
    }

    public function test_admin_cookie_settings(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/cookies')->assertOk();

        Livewire::test(ManageCookieSettings::class)
            ->fillForm(['enabled' => true, 'marketing_code' => '<script>pixel()</script>'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(app(CookieSettings::class)->enabled);
    }

    public function test_customer_can_withdraw_and_receives_confirmation(): void
    {
        $order = $this->placeOrder(2);
        Mail::fake();

        $this->get($order->publicUrl())->assertSee('Odstúpiť od zmluvy');
        $this->get(route('returns.create', $order->token))->assertOk()->assertSee('Etiópia');

        $item = $order->items->first();
        $this->post(route('returns.store', $order->token), ['type' => 'withdrawal', 'quantities' => [$item->id => 0]])->assertSessionHasErrors('quantities');
        $this->post(route('returns.store', $order->token), ['type' => 'withdrawal', 'quantities' => [$item->id => 5], 'refund_account' => 'SK31 1200 0000 1987 4263 7541'])
            ->assertRedirect($order->publicUrl());

        $request = ReturnRequest::query()->firstOrFail();
        $this->assertSame(ReturnType::Withdrawal, $request->type);
        $this->assertSame('R'.date('Y').'00001', $request->number);
        $this->assertSame(2, $request->items[0]['quantity']);
        $this->assertSame('SK3112000000198742637541', $request->refund_account);
        $this->assertSame(now()->addDays(14)->toDateString(), $request->deadline_at->toDateString());

        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'returns.received' && $mail->hasTo('jana@example.com'));
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'returns.notification');

        $this->get($order->publicUrl())->assertSee($request->number);
    }

    public function test_complaint_requires_description_and_has_30_day_deadline(): void
    {
        $order = $this->placeOrder(1);
        $item = $order->items->first();

        $this->post(route('returns.store', $order->token), ['type' => 'complaint', 'quantities' => [$item->id => 1]])
            ->assertSessionHasErrors(['reason', 'preferred_resolution']);

        $this->post(route('returns.store', $order->token), ['type' => 'complaint', 'quantities' => [$item->id => 1], 'reason' => 'Mlynček nemelie.', 'preferred_resolution' => 'replacement']);

        $request = ReturnRequest::query()->firstOrFail();
        $this->assertSame(ReturnType::Complaint, $request->type);
        $this->assertSame(now()->addDays(30)->toDateString(), $request->deadline_at->toDateString());
    }

    public function test_cancelled_order_has_no_return_form(): void
    {
        $order = $this->placeOrder();
        $order->update(['status' => OrderStatus::Cancelled]);

        $this->get(route('returns.create', $order->token))->assertNotFound();
    }

    public function test_admin_resolves_request_and_customer_is_notified(): void
    {
        $order = $this->placeOrder(1);
        $this->post(route('returns.store', $order->token), ['type' => 'complaint', 'quantities' => [$order->items->first()->id => 1], 'reason' => 'Vada.', 'preferred_resolution' => 'repair']);
        $request = ReturnRequest::query()->firstOrFail();
        Mail::fake();
        $this->actingAs($this->superAdmin());

        foreach (['/admin/returns', '/admin/returns/'.$request->id, '/admin/orders/'.$order->id] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get('/admin/returns')->assertSee($request->number);

        Livewire::test(ViewReturnRequest::class, ['record' => $request->id])
            ->callAction('changeStatus', ['status' => ReturnStatus::Completed->value, 'resolution' => 'Opravené, posielame späť.', 'notify' => true])
            ->assertHasNoActionErrors();

        $request->refresh();
        $this->assertSame(ReturnStatus::Completed, $request->status);
        $this->assertNotNull($request->resolved_at);
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'returns.status' && str_contains($mail->variables['resolution'], 'Opravené'));
        $this->get($order->publicUrl())->assertSee('Opravené, posielame späť.');
    }
}
