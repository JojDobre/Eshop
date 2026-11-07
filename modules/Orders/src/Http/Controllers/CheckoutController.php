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
use Modules\Orders\Coupons;
use Modules\Orders\Http\Requests\CheckoutRequest;
use Modules\Orders\Models\Coupon;
use Modules\Orders\Payments\PaymentGateways;
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

        $coupon = app(Coupons::class)->forCart($summary);
        $weight = $summary->weight();
        $shippingMethods = ShippingMethod::query()->active()->get()->filter(fn (ShippingMethod $method) => $method->isAvailableForWeight($weight))->values();
        $gateways = app(PaymentGateways::class);
        $paymentMethods = PaymentMethod::query()->active()->with('shippingMethods')->get()
            ->filter(fn (PaymentMethod $method) => $gateways->supports($method->type))->values();
        $afterDiscount = $summary->subtotal() - ($coupon?->discountFor($summary->subtotal()) ?? 0);

        return view('theme::templates.checkout', [
            'summary' => $summary,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            // Ktoré platby sú povolené pri ktorej doprave (pre prepínanie v prehliadači).
            'paymentAvailability' => $shippingMethods->mapWithKeys(fn (ShippingMethod $shipping) => [
                $shipping->id => $paymentMethods->filter(fn (PaymentMethod $payment) => $payment->isAvailableFor($shipping))->pluck('id')->values(),
            ]),
            'shippingPrices' => $shippingMethods->mapWithKeys(fn (ShippingMethod $shipping) => [
                $shipping->id => $coupon?->givesFreeShipping() ? 0 : $shipping->priceFor($afterDiscount, $weight),
            ]),
            'totals' => CheckoutTotals::calculate($summary, null, null, $coupon),
            'coupon' => $coupon,
            'countries' => config('eshop.countries'),
            'legalPages' => app(LegalPages::class),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $summary = $this->cart->summary();
        $shipping = $request->shippingMethod();
        $payment = $request->paymentMethod();

        if ($shipping === null || $payment === null || ! app(PaymentGateways::class)->supports($payment->type) || ! $payment->isAvailableFor($shipping) || ! $shipping->isAvailableForWeight($summary->weight())) {
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
                coupon: Coupon::findByCode($this->cart->couponCode()),
            );
        } catch (CheckoutException $e) {
            return redirect()->route('cart.show')->with('error', $e->getMessage());
        }

        $this->cart->clear();
        session()->flash('order_placed', true);

        // Platba kartou: hneď na platobnú bránu (stránka objednávky ostane ako návrat aj pri zrušení platby).
        if (app(PaymentGateways::class)->forPayableOrder($order) !== null) {
            return redirect()->route('orders.pay', $order->token);
        }

        return redirect()->to($order->publicUrl());
    }
}
