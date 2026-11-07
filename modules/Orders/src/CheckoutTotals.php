<?php

namespace Modules\Orders;

use Modules\Cart\CartSummary;
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
    ) {}

    public static function calculate(CartSummary $cart, ?ShippingMethod $shipping, ?PaymentMethod $payment): self
    {
        $subtotal = $cart->subtotal();

        return new self(
            subtotal: $subtotal,
            shipping: $shipping?->priceFor($subtotal) ?? 0,
            paymentFee: $payment?->fee ?? 0,
            currency: $cart->currency,
        );
    }

    public function total(): int
    {
        return $this->subtotal + $this->shipping + $this->paymentFee;
    }
}
