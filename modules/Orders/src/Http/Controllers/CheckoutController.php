<?php

namespace Modules\Orders\Http\Controllers;

use App\Core\Content\ContentLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Modules\Cart\CartManager;
use Modules\Orders\Actions\PlaceOrder;
use Modules\Orders\CheckoutException;
use Modules\Orders\CheckoutTotals;
use Modules\Orders\Http\Requests\CheckoutRequest;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Storefront\Pages\LegalPages;

class CheckoutController extends Controller
{
    public function __construct(private readonly CartManager $cart) {}

    public function show(): View|RedirectResponse
    {
        $summary = $this->cart->summary();

        if (! $summary->canCheckout()) {
            return redirect()->route('cart.show');
        }

        $shippingMethods = ShippingMethod::query()->active()->get();
        $paymentMethods = PaymentMethod::query()->active()->with('shippingMethods')->get();

        return view('theme::templates.checkout', [
            'summary' => $summary,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            // Ktoré platby sú povolené pri ktorej doprave (pre prepínanie v prehliadači).
            'paymentAvailability' => $shippingMethods->mapWithKeys(fn (ShippingMethod $shipping) => [
                $shipping->id => $paymentMethods->filter(fn (PaymentMethod $payment) => $payment->isAvailableFor($shipping))->pluck('id')->values(),
            ]),
            'shippingPrices' => $shippingMethods->mapWithKeys(fn (ShippingMethod $shipping) => [$shipping->id => $shipping->priceFor($summary->subtotal())]),
            'totals' => CheckoutTotals::calculate($summary, null, null),
            'countries' => config('eshop.countries'),
            'legalPages' => app(LegalPages::class),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $summary = $this->cart->summary();
        $shipping = $request->shippingMethod();
        $payment = $request->paymentMethod();

        if ($shipping === null || $payment === null || ! $payment->isAvailableFor($shipping)) {
            return back()->withInput()->withErrors(['payment_method_id' => __('orders::messages.payment_not_available')]);
        }

        try {
            $order = $placeOrder->handle(
                cart: $summary,
                shipping: $shipping,
                payment: $payment,
                customer: $request->customerData(),
                locale: app(ContentLocale::class)->current(),
                ipAddress: $request->ip(),
            );
        } catch (CheckoutException $e) {
            return redirect()->route('cart.show')->with('error', $e->getMessage());
        }

        $this->cart->clear();

        return redirect()->to($order->publicUrl())->with('order_placed', true);
    }
}
