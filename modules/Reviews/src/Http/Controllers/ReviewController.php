<?php

namespace Modules\Reviews\Http\Controllers;

use App\Core\Content\ContentLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Modules\Catalog\Models\Product;
use Modules\Customers\Models\Customer;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Reviews;
use Modules\Reviews\ReviewSettings;
use Throwable;

class ReviewController extends Controller
{
    /** Najkratší čas medzi zobrazením formulára a odoslaním (sekundy) – ochrana pred robotmi. */
    public const MIN_SECONDS = 3;

    public function __construct(private readonly Reviews $reviews) {}

    /**
     * Hodnotenie z detailu produktu.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless(ReviewSettings::isEnabled() && $product->is_visible, 404);

        $customer = auth('customer')->user();
        $customer = $customer instanceof Customer ? $customer : null;
        $back = redirect()->to($product->url().'#hodnotenia');

        if (! $this->reviews->canReview($product, $customer)) {
            return $back->with('review_status', __('reviews::shop.cannot_review'));
        }

        $data = $this->validated($request, $customer === null);

        if ($this->isBot($request)) {
            return $back->with('review_status', __('reviews::shop.thanks_pending'));
        }

        $this->create($product, $data, $customer, $this->reviews->purchaseOrder($product, $customer), $request);

        return $back->with('review_status', __($this->reviews->initialStatus() === Review::APPROVED ? 'reviews::shop.thanks_published' : 'reviews::shop.thanks_pending'));
    }

    /**
     * Stránka z e-mailu: hodnotenie všetkých produktov objednávky (podpísaný odkaz = overený nákup).
     */
    public function order(Order $order): View
    {
        abort_unless(ReviewSettings::isEnabled(), 404);

        $order->load('items.product');
        $reviewed = Review::query()->where('order_id', $order->id)->pluck('product_id')->all();

        return view('reviews::order', [
            'order' => $order,
            'products' => $order->items->pluck('product')->filter()->unique('id')->values(),
            'reviewed' => $reviewed,
        ]);
    }

    public function storeForOrder(Request $request, Order $order): RedirectResponse
    {
        abort_unless(ReviewSettings::isEnabled(), 404);

        $product = $order->items()->where('product_id', $request->integer('product_id'))->first()?->product;
        abort_if($product === null, 404);

        if (! Review::query()->where('order_id', $order->id)->where('product_id', $product->id)->exists()) {
            $data = $this->validated($request, false);
            $data['author_name'] = $data['author_name'] ?? $order->billing_name;
            $customer = $order->customer_id ? Customer::query()->find($order->customer_id) : null;
            $this->create($product, [...$data, 'email' => $order->email], $customer, $order, $request);
        }

        return back()->with('review_status', __($this->reviews->initialStatus() === Review::APPROVED ? 'reviews::shop.thanks_published' : 'reviews::shop.thanks_pending'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $guest): array
    {
        return $request->validate([
            'author_name' => [$guest ? 'required' : 'nullable', 'string', 'max:80'],
            'email' => [$guest ? 'required' : 'nullable', 'email', 'max:255'],
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:3000'],
            'pros' => ['nullable', 'string', 'max:1000'],
            'cons' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function create(Product $product, array $data, ?Customer $customer, ?Order $order, Request $request): Review
    {
        return Review::query()->create([
            ...$data,
            'product_id' => $product->id,
            'customer_id' => $customer?->id,
            'order_id' => $order?->id,
            'author_name' => $data['author_name'] ?? $customer?->name ?? __('reviews::shop.anonymous'),
            'email' => $data['email'] ?? $customer?->email,
            'status' => $this->reviews->initialStatus(),
            'locale' => app(ContentLocale::class)->current(),
            'ip_address' => $request->ip(),
        ]);
    }

    private function isBot(Request $request): bool
    {
        if (filled($request->input('website'))) {
            return true;
        }

        try {
            return time() - (int) Crypt::decryptString((string) $request->input('form_token')) < self::MIN_SECONDS;
        } catch (Throwable) {
            return true;
        }
    }
}
