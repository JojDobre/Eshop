<?php

namespace Modules\Orders;

use Modules\Cart\CartSummary;
use Modules\Orders\Models\Coupon;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;

/**
 * Sumy objednávky: tovar + doprava + poplatok za platbu. Všetko s DPH, v najmenších jednotkách.
 */
final class CheckoutTotals
{
    public function __construct(
        public readonly int $subtotal,
        public readonly int $shipping,
        public readonly int $paymentFee,
        public readonly string $currency,
        public readonly int $discount = 0,
        public readonly ?Coupon $coupon = null,
    ) {}

    /**
     * @param  Coupon|null  $coupon  Platný kupón (overený cez Coupon::rejectionReason).
     */
    public static function calculate(CartSummary $cart, ?ShippingMethod $shipping, ?PaymentMethod $payment, ?Coupon $coupon = null): self
    {
        $subtotal = $cart->subtotal();
        $discount = $coupon?->discountFor($subtotal) ?? 0;
        // Doprava zadarmo „od sumy" sa posudzuje podľa sumy po zľave.
        $shippingPrice = $coupon?->givesFreeShipping() ? 0 : ($shipping?->priceFor($subtotal - $discount, $cart->weight()) ?? 0);

        return new self(
            subtotal: $subtotal,
            shipping: $shippingPrice,
            paymentFee: $payment?->fee ?? 0,
            currency: $cart->currency,
            discount: $discount,
            coupon: $coupon,
        );
    }

    public function total(): int
    {
        return $this->subtotal - $this->discount + $this->shipping + $this->paymentFee;
    }
}
