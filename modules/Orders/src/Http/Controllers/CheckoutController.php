<?php

namespace Modules\Orders\Http\Controllers;

use App\Core\Content\ContentLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Collection;
use Modules\Cart\CartManager;
use Modules\Cart\CartSummary;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldValues;
use Modules\Orders\Actions\PlaceOrder;
use Modules\Orders\CheckoutException;
use Modules\Orders\CheckoutFields;
use Modules\Orders\CheckoutTotals;
use Modules\Orders\Coupons;
use Modules\Orders\GiftVouchers;
use Modules\Orders\Http\Requests\CheckoutRequest;
use Modules\Orders\Models\Coupon;
use Modules\Orders\Payments\PaymentGateways;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;
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
        $voucher = app(GiftVouchers::class)->forCart($summary);
        $weight = $summary->weight();
        $shippingMethods = self::availableShipping($summary);
        $gateways = app(PaymentGateways::class);
        $paymentMethods = PaymentMethod::query()->active()->with('shippingMethods')->get()
            ->filter(fn (PaymentMethod $method) => $gateways->supports($method->type))->values();
        $afterDiscount = $summary->subtotal() - ($coupon?->discountFor($summary->subtotal(), $summary->currency) ?? 0);

        return view('theme::templates.checkout', [
            'summary' => $summary,
            'shippingMethods' => $shippingMethods,
            'paymentMethods' => $paymentMethods,
            // Ktoré platby sú povolené pri ktorej doprave (pre prepínanie v prehliadači).
            'paymentAvailability' => $shippingMethods->mapWithKeys(fn (ShippingMethod $shipping) => [
                $shipping->id => $paymentMethods->filter(fn (PaymentMethod $payment) => $payment->isAvailableFor($shipping))->pluck('id')->values(),
            ]),
            'shippingPrices' => $shippingMethods->mapWithKeys(fn (ShippingMethod $shipping) => [
                $shipping->id => $coupon?->givesFreeShipping() ? 0 : $shipping->priceFor($afterDiscount, $weight, $summary->currency),
            ]),
            'totals' => CheckoutTotals::calculate($summary, null, null, $coupon),
            'coupon' => $coupon,
            'giftVoucher' => $voucher,
            'countries' => config('eshop.countries'),
            'legalPages' => app(LegalPages::class),
            'checkoutFields' => CheckoutFields::fields(),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
    {
        $summary = $this->cart->summary();
        $shipping = $request->shippingMethod();
        $payment = $request->paymentMethod();

        if ($shipping !== null && ! self::availableShipping($summary)->contains('id', $shipping->id)) {
            return back()->withInput()->withErrors(['shipping_method_id' => __('orders::messages.shipping_not_available')]);
        }

        if ($shipping === null || $payment === null || ! app(PaymentGateways::class)->supports($payment->type) || ! $payment->isAvailableFor($shipping)) {
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
                voucher: app(GiftVouchers::class)->forCart($summary),
            );
        } catch (CheckoutException $e) {
            return redirect()->route('cart.show')->with('error', $e->getMessage());
        }

        // Polia, ktoré zákazník vyplnil v pokladni (vlastné polia objednávky).
        if (CheckoutFields::fields()->isNotEmpty()) {
            app(CustomFieldValues::class)->save(CustomField::ORDER, $order->id, (array) $request->input('custom_fields', []));
        }

        $this->cart->clear();
        app(GiftVouchers::class)->remove();
        session()->flash('order_placed', true);

        // Platba kartou: hneď na platobnú bránu (stránka objednávky ostane ako návrat aj pri zrušení platby).
        if (app(PaymentGateways::class)->forPayableOrder($order) !== null) {
            return redirect()->route('orders.pay', $order->token);
        }

        return redirect()->to($order->publicUrl());
    }

    /**
     * Doprava pre košík: podľa hmotnosti, a „Bez dopravy“ iba pre košík bez tovaru na doručenie
     * (služby, digitálne produkty). Ak „Bez dopravy“ nie je, ponúknu sa bežné spôsoby.
     *
     * @return Collection<int, ShippingMethod>
     */
    public static function availableShipping(CartSummary $summary): Collection
    {
        $weight = $summary->weight();
        // Typy a kategórie produktov v košíku (doprava môže niektoré vylúčiť, napr. zvieratá iba osobne).
        $typeIds = collect($summary->lines)->map(fn ($line) => $line->product->product_type_id)->unique()->values()->all();
        $categoryIds = collect($summary->lines)->flatMap(fn ($line) => $line->product->categories->pluck('id'))->unique()->values()->all();
        $subtotal = $summary->subtotal();
        $methods = ShippingMethod::query()->active()->get()->filter(fn (ShippingMethod $method) => $method->isAvailableForWeight($weight)
            && $method->allowsProducts($typeIds, $categoryIds)
            && $method->allowsOrderValue($subtotal, $summary->currency));
        $none = $methods->filter(fn (ShippingMethod $method) => $method->type === ShippingType::None);
        $regular = $methods->reject(fn (ShippingMethod $method) => $method->type === ShippingType::None);

        if (! $summary->requiresShipping() && $none->isNotEmpty()) {
            return $none->values();
        }

        return $regular->values();
    }
}
