<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Customers\Models\Customer;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Payments\PaymentType;
use Modules\Reviews\Filament\Resources\Reviews\Pages\ManageReviews;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Reviews;
use Modules\Reviews\ReviewSettings;
use Modules\Reviews\SendReviewRequests;

class ReviewsTest extends ShopTestCase
{
    private function form(array $data = []): array
    {
        return ['rating' => 5, 'title' => 'Výborná káva', 'body' => 'Chutí skvele.', 'form_token' => Crypt::encryptString((string) (time() - 10)), ...$data];
    }

    private function order(): Order
    {
        $this->post(route('cart.add'), ['product_id' => $this->product('Káva')->id]);
        $this->payment(PaymentType::BankTransfer)->shippingMethods()->sync([]);
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $this->pickup()->id]))->assertSessionDoesntHaveErrors();

        return Order::query()->sole();
    }

    public function test_guest_review_is_moderated_and_shown_after_approval(): void
    {
        $product = $this->product('Čaj');

        $this->post(route('reviews.store', $product), $this->form(['rating' => 4, 'author_name' => 'Peter', 'email' => 'peter@example.com']))
            ->assertRedirect($product->url().'#hodnotenia')->assertSessionHas('review_status', __('reviews::shop.thanks_pending'));

        $review = Review::query()->sole();
        $this->assertSame(Review::PENDING, $review->status);
        $this->assertFalse($review->isVerified());
        $this->get($product->url())->assertDontSee('Výborná káva')->assertSee(__('reviews::shop.none'));

        $review->update(['status' => Review::APPROVED, 'reply' => 'Ďakujeme, Peter!']);
        $product->refresh();
        $this->assertSame(1, $product->reviews_count);
        $this->assertEquals(4.0, (float) $product->rating_average);

        $this->get($product->url())->assertSee('Výborná káva')->assertSee('Ďakujeme, Peter!')
            ->assertSee('"aggregateRating"', false)->assertSee('"ratingValue":"4.0"', false);
        // Hviezdičky vo výpise produktov.
        $this->get('/')->assertSee(__('reviews::shop.stars', ['rating' => '4,0']));
    }

    public function test_bots_and_validation(): void
    {
        $product = $this->product('Čaj');

        // Vyplnené skryté pole alebo príliš rýchle odoslanie: tváriť sa ako úspech, nič neuložiť.
        $this->post(route('reviews.store', $product), $this->form(['author_name' => 'Bot', 'email' => 'b@example.com', 'website' => 'spam']))->assertSessionHas('review_status');
        $this->post(route('reviews.store', $product), $this->form(['author_name' => 'Bot', 'email' => 'b@example.com', 'form_token' => Crypt::encryptString((string) time())]));
        $this->assertSame(0, Review::query()->count());

        $this->post(route('reviews.store', $product), $this->form(['rating' => 7]))->assertSessionHasErrors(['rating', 'author_name', 'email']);
    }

    public function test_verified_purchase_by_logged_in_customer_and_verified_only_mode(): void
    {
        app(ReviewSettings::class)->fill(['verified_only' => true, 'require_approval' => false])->save();
        $order = $this->order();
        $product = $order->items->first()->product;
        $customer = Customer::query()->create(['name' => 'Jana Nováková', 'email' => 'jana@example.com', 'password' => 'tajne123']);

        // Bez nákupu sa hodnotiť nedá.
        $other = $this->product('Hrnček');
        $this->actingAs($customer, 'customer');
        $this->get($other->url())->assertSee(__('reviews::shop.only_buyers'));
        $this->post(route('reviews.store', $other), $this->form())->assertSessionHas('review_status', __('reviews::shop.cannot_review'));

        $this->post(route('reviews.store', $product), $this->form())->assertSessionHas('review_status', __('reviews::shop.thanks_published'));
        $review = Review::query()->sole();
        $this->assertTrue($review->isVerified());
        $this->assertSame('Jana Nováková', $review->author_name);
        $this->get($product->url())->assertSee(__('reviews::shop.verified'));

        // Druhé hodnotenie toho istého produktu nie.
        $this->assertFalse(app(Reviews::class)->canReview($product, $customer));
    }

    public function test_review_request_email_after_completed_order(): void
    {
        Mail::fake();
        app(ReviewSettings::class)->fill(['request_delay_days' => 7])->save();
        $order = $this->order();

        app(ChangeOrderStatus::class)->handle($order, OrderStatus::Completed, notifyCustomer: false);
        $this->assertNotNull($order->fresh()->review_request_due_at);
        $this->assertSame(0, app(SendReviewRequests::class)->handle());

        $this->travel(8)->days();
        $this->assertSame(1, app(SendReviewRequests::class)->handle());
        $this->assertSame(0, app(SendReviewRequests::class)->handle());

        $url = null;
        Mail::assertSent(TemplateMail::class, function (TemplateMail $mail) use (&$url) {
            $url = $mail->variables['review_url'] ?? null;

            return $mail->templateKey === 'reviews.request';
        });

        // Stránka z e-mailu: hodnotenie je overený nákup aj bez prihlásenia.
        $this->get($url)->assertOk()->assertSee('Káva');
        $this->post($url, $this->form(['product_id' => $order->items->first()->product_id]))->assertSessionHas('review_status');
        $review = Review::query()->sole();
        $this->assertSame($order->id, $review->order_id);
        $this->assertSame($order->billing_name, $review->author_name);

        // Bez podpisu sa stránka neotvorí.
        $this->get(route('reviews.order', $order))->assertForbidden();
    }

    public function test_admin_moderates_reviews_and_feature_can_be_disabled(): void
    {
        $product = $this->product('Čaj');
        $review = Review::query()->create(['product_id' => $product->id, 'author_name' => 'Eva', 'rating' => 2, 'status' => Review::PENDING]);

        $this->actingAs($this->superAdmin());
        $this->get('/admin/reviews')->assertOk()->assertSee('Eva');
        $this->get('/admin/settings/reviews')->assertOk();

        Livewire::test(ManageReviews::class)->callTableAction('approve', $review);
        $this->assertSame(Review::APPROVED, $review->fresh()->status);
        $this->assertSame(1, $product->fresh()->reviews_count);

        app(ReviewSettings::class)->fill(['enabled' => false])->save();
        $this->get('/admin/reviews')->assertForbidden();
        $this->get($product->url())->assertDontSee(__('reviews::shop.heading'));
        $this->post(route('reviews.store', $product), $this->form())->assertNotFound();
    }
}
