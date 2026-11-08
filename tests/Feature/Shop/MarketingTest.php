<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\TemplateMail;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Modules\Cart\CartManager;
use Modules\Cart\Models\Cart;
use Modules\Catalog\Models\Category;
use Modules\Marketing\AbandonedCarts;
use Modules\Marketing\Http\Controllers\FeedController;
use Modules\Marketing\MarketingSettings;
use Modules\Marketing\Models\Subscriber;
use Modules\Orders\Models\Order;
use Modules\Payments\PaymentType;
use Modules\Storefront\Cookies\CookieConsent;

class MarketingTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        FeedController::flush();
    }

    private function settings(array $values): void
    {
        app(MarketingSettings::class)->fill($values)->save();
    }

    public function test_newsletter_double_opt_in_unsubscribe_and_mailchimp_sync(): void
    {
        $this->settings(['newsletter_enabled' => true, 'newsletter_provider' => 'mailchimp', 'mailchimp_api_key' => 'abc123-us21', 'mailchimp_list_id' => 'list1']);
        Http::fake(['us21.api.mailchimp.com/*' => Http::response(['id' => 'x'])]);

        $this->get('/')->assertSee(__('marketing::shop.newsletter_title'));
        $this->post(route('newsletter.subscribe'), ['email' => 'Jana@Example.com'])->assertSessionHasErrors('consent');
        $this->post(route('newsletter.subscribe'), ['email' => 'Jana@Example.com', 'consent' => '1'])->assertSessionHas('success');

        $subscriber = Subscriber::query()->sole();
        $this->assertSame('jana@example.com', $subscriber->email);
        $this->assertSame(Subscriber::PENDING, $subscriber->status);
        Http::assertNothingSent();
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'marketing.newsletter_confirm' && $mail->variables['confirm_url'] === $subscriber->confirmUrl());

        $this->get($subscriber->confirmUrl())->assertOk()->assertSee(__('marketing::shop.newsletter_confirmed'));
        $this->assertSame(Subscriber::SUBSCRIBED, $subscriber->fresh()->status);
        Http::assertSent(fn (Request $request) => $request->method() === 'PUT'
            && $request->url() === 'https://us21.api.mailchimp.com/3.0/lists/list1/members/'.md5('jana@example.com')
            && $request['status'] === 'subscribed' && $request->header('Authorization')[0] === 'Basic '.base64_encode('eshop:abc123-us21'));

        $this->get($subscriber->unsubscribeUrl())->assertOk();
        $this->assertSame(Subscriber::UNSUBSCRIBED, $subscriber->fresh()->status);
        Http::assertSent(fn (Request $request) => $request->method() === 'PUT' && $request['status'] === 'unsubscribed');
    }

    public function test_checkout_subscription_with_ecomail_and_bots(): void
    {
        $this->settings(['newsletter_enabled' => true, 'newsletter_provider' => 'ecomail', 'ecomail_api_key' => 'key-1', 'ecomail_list_id' => '7']);
        Http::fake(['api2.ecomailapp.cz/*' => Http::response(['id' => 1])]);

        $this->post(route('newsletter.subscribe'), ['email' => 'bot@example.com', 'consent' => '1', 'website' => 'spam']);
        $this->assertSame(0, Subscriber::query()->count());

        $this->post(route('cart.add'), ['product_id' => $this->product('Káva')->id]);
        $this->payment(PaymentType::BankTransfer)->shippingMethods()->sync([]);
        $this->get(route('checkout.show'))->assertSee(__('marketing::shop.newsletter_checkout'));
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $this->pickup()->id, 'newsletter' => '1']))->assertSessionDoesntHaveErrors();

        $subscriber = Subscriber::query()->sole();
        $this->assertSame('checkout', $subscriber->source);
        $this->get($subscriber->confirmUrl());
        Http::assertSent(fn (Request $request) => $request->url() === 'https://api2.ecomailapp.cz/lists/7/subscribe'
            && $request->header('key')[0] === 'key-1' && $request['subscriber_data']['email'] === 'jana@example.com');
    }

    public function test_abandoned_cart_reminder_and_restore(): void
    {
        $this->settings(['abandoned_enabled' => true, 'abandoned_delay_hours' => 3]);
        $this->post(route('cart.add'), ['product_id' => $this->product('Káva')->id, 'quantity' => 2]);
        $this->postJson(route('marketing.cart.email'), ['email' => 'Peter@Example.com'])->assertJson(['saved' => true]);

        $cart = Cart::query()->sole();
        $this->assertSame('peter@example.com', $cart->email);
        $this->assertSame(0, app(AbandonedCarts::class)->send());

        $this->travel(4)->hours();
        $this->assertSame(1, app(AbandonedCarts::class)->send());
        $this->assertSame(0, app(AbandonedCarts::class)->send());
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'marketing.abandoned_cart'
            && str_contains($mail->variables['cart_items'], 'Káva × 2') && $mail->hasTo('peter@example.com'));

        // Odkaz obnoví košík v inom prehliadači.
        $this->app['session']->flush();
        $this->get(route('marketing.cart.restore', $cart->token))->assertRedirect(route('cart.show'));
        $this->assertSame($cart->id, app(CartManager::class)->current()?->id);

        // Vypnuté: e-mail sa neukladá.
        $this->settings(['abandoned_enabled' => false]);
        $this->postJson(route('marketing.cart.email'), ['email' => 'x@example.com'])->assertJson(['saved' => false]);
    }

    public function test_heureka_and_google_feeds(): void
    {
        $category = Category::query()->create(['name' => 'Káva']);
        $product = $this->product('Etiópia Yirgacheffe', '12.90', category: $category, attributes: ['ean' => '8586000000017', 'sku' => 'ETI-1', 'description' => '<p>Kvetinová káva</p>']);
        $this->product('Skrytý', attributes: ['is_visible' => false]);

        $this->get(route('marketing.feed', 'heureka'))->assertNotFound();
        $this->settings(['feeds_enabled' => true]);

        $heureka = $this->get(route('marketing.feed', 'heureka'))->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->streamedContent();
        $xml = simplexml_load_string($heureka);
        $this->assertCount(1, $xml->SHOPITEM);
        $this->assertSame((string) $product->id, (string) $xml->SHOPITEM->ITEM_ID);
        $this->assertSame('12.90', (string) $xml->SHOPITEM->PRICE_VAT);
        $this->assertSame('8586000000017', (string) $xml->SHOPITEM->EAN);
        $this->assertSame('Káva', (string) $xml->SHOPITEM->CATEGORYTEXT);
        $this->assertSame('Kvetinová káva', (string) $xml->SHOPITEM->DESCRIPTION);

        $google = $this->get(route('marketing.feed', 'google'))->streamedContent();
        $xml = simplexml_load_string($google);
        $item = $xml->channel->item->children('http://base.google.com/ns/1.0');
        $this->assertSame('12.90 EUR', (string) $item->price);
        $this->assertSame('in_stock', (string) $item->availability);
        $this->assertSame('8586000000017', (string) $item->gtin);
        $this->assertSame('new', (string) $item->condition);
    }

    public function test_tracking_codes_require_consent_and_send_purchase(): void
    {
        $this->settings(['ga4_id' => 'G-TEST1234', 'meta_pixel_id' => '1234567890123']);
        $product = $this->product('Káva');

        $this->get('/')->assertDontSee('G-TEST1234');
        $this->withUnencryptedCookie(CookieConsent::COOKIE, json_encode(['analytics' => true, 'marketing' => true]));
        $this->get('/')->assertSee('googletagmanager.com/gtag/js?id=G-TEST1234', false)->assertSee("fbq('init', \"1234567890123\")", false);

        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->payment(PaymentType::BankTransfer)->shippingMethods()->sync([]);
        $response = $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $this->pickup()->id]));
        $order = Order::query()->sole();

        $this->get($response->headers->get('Location'))->assertSee("gtag('event', 'purchase'", false)
            ->assertSee('"transaction_id":"'.$order->number.'"', false)->assertSee("fbq('track', 'Purchase'", false);
    }
}
