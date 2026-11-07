<?php

namespace Modules\Orders;

use Modules\Cart\CartSummary;
use Modules\Catalog\Models\Product;
use Modules\Orders\Models\Coupon;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\TaxCalculator;
use Modules\Tax\TaxContext;
use Modules\Tax\TaxRules;

/**
 * Sumy objednávky: tovar + doprava + poplatok za platbu. Všetko s DPH, v najmenších jednotkách.
 *
 * Pri DPH krajiny doručenia (OSS) alebo prenesení daňovej povinnosti sa ceny a sadzby položiek upravia.
 */
final class CheckoutTotals
{
    /**
     * @param  array<int, array{unit: int, total: int, rate: float}>  $lines  Položky podľa indexu v košíku.
     */
    public function __construct(
        public readonly int $subtotal,
        public readonly int $shipping,
        public readonly int $paymentFee,
        public readonly string $currency,
        public readonly int $discount = 0,
        public readonly ?Coupon $coupon = null,
        public readonly array $lines = [],
        public readonly ?float $serviceRate = null,
        public readonly ?TaxContext $tax = null,
    ) {}

    /**
     * @param  Coupon|null  $coupon  Platný kupón (overený cez Coupon::rejectionReason).
     */
    public static function calculate(CartSummary $cart, ?ShippingMethod $shipping, ?PaymentMethod $payment, ?Coupon $coupon = null, ?TaxContext $tax = null): self
    {
        $calculator = app(TaxCalculator::class);
        $rules = app(TaxRules::class);
        $lines = [];

        foreach ($cart->lines as $index => $line) {
            $homeRate = $calculator->effectiveRate($line->product->vatRate());
            $rate = $tax === null ? $homeRate : $rules->rate($tax, $homeRate, self::taxClass($line->product));
            $unit = $tax === null ? (int) $line->unitPrice : $rules->gross($tax, (int) $line->unitPrice, $homeRate, $rate);
            $lines[$index] = ['unit' => $unit, 'total' => $unit * $line->quantity, 'rate' => $rate];
        }

        $subtotal = $tax === null ? $cart->subtotal() : array_sum(array_column($lines, 'total'));
        $discount = $coupon?->discountFor($subtotal, $cart->currency) ?? 0;
        // Doprava zadarmo „od sumy" sa posudzuje podľa sumy po zľave.
        $shippingPrice = $coupon?->givesFreeShipping() ? 0 : ($shipping?->priceFor($subtotal - $discount, $cart->weight(), $cart->currency) ?? 0);
        $paymentFee = $payment?->feeIn($cart->currency) ?? 0;

        // Doprava a platba sa zdaňujú predvolenou (základnou) sadzbou.
        $homeServiceRate = $calculator->effectiveRate(Product::defaultVatRate());
        $serviceRate = $tax === null ? $homeServiceRate : $rules->rate($tax, $homeServiceRate, TaxRate::default());

        if ($tax !== null) {
            $shippingPrice = $rules->gross($tax, $shippingPrice, $homeServiceRate, $serviceRate);
            $paymentFee = $rules->gross($tax, $paymentFee, $homeServiceRate, $serviceRate);
        }

        return new self(
            subtotal: $subtotal,
            shipping: $shippingPrice,
            paymentFee: $paymentFee,
            currency: $cart->currency,
            discount: $discount,
            coupon: $coupon,
            lines: $lines,
            serviceRate: $serviceRate,
            tax: $tax,
        );
    }

    public function total(): int
    {
        return $this->subtotal - $this->discount + $this->shipping + $this->paymentFee;
    }

    private static function taxClass(Product $product): ?TaxRate
    {
        return $product->tax_rate_id !== null ? $product->taxRate : TaxRate::default();
    }
}
