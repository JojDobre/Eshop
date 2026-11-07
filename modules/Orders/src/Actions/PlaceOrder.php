<?php

namespace Modules\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cart\CartSummary;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\StockMovement;
use Modules\Catalog\StockService;
use Modules\Orders\CheckoutException;
use Modules\Orders\CheckoutTotals;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Models\Coupon;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderHistoryEntry;
use Modules\Orders\NumberSequence;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Storefront\Pages\LegalPages;
use Modules\Tax\TaxCalculator;

/**
 * Vytvorí objednávku z košíka: overí dostupnosť, odpíše sklad a uloží kópiu všetkých údajov.
 */
class PlaceOrder
{
    public function __construct(
        private readonly NumberSequence $numbers,
        private readonly StockService $stock,
        private readonly TaxCalculator $tax,
    ) {}

    /**
     * @param  array<string, mixed>  $customer  Overené údaje z pokladne (CheckoutRequest::customerData()).
     */
    public function handle(
        CartSummary $cart,
        ShippingMethod $shipping,
        PaymentMethod $payment,
        array $customer,
        string $locale,
        ?string $ipAddress = null,
        ?Coupon $coupon = null,
    ): Order {
        if (! $cart->canCheckout()) {
            throw new CheckoutException(__('orders::messages.cart_changed'));
        }

        if (! $shipping->is_active || ! $payment->is_active || ! $payment->isAvailableFor($shipping)) {
            throw new CheckoutException(__('orders::messages.payment_not_available'));
        }

        $order = DB::transaction(function () use ($cart, $shipping, $payment, $customer, $locale, $ipAddress, $coupon): Order {
            $number = $this->numbers->nextOrderNumber();
            $this->reserveStock($cart, $number);
            $coupon = $this->claimCoupon($coupon, $cart);

            $totals = CheckoutTotals::calculate($cart, $shipping, $payment, $coupon);
            $needsAddress = $shipping->type->requiresShippingAddress();
            // Doprava a platba sa zdaňujú predvolenou (základnou) sadzbou.
            $serviceRate = $this->tax->effectiveRate(Product::defaultVatRate());

            $order = Order::query()->create([
                ...$customer,
                ...($needsAddress ? [] : [
                    'shipping_name' => null, 'shipping_street' => null, 'shipping_city' => null,
                    'shipping_zip' => null, 'shipping_country' => null,
                ]),
                'number' => $number,
                'vat_payer' => $this->tax->isVatPayer(),
                'shipping_tax_rate' => $serviceRate,
                'payment_tax_rate' => $serviceRate,
                'token' => Str::random(48),
                'status' => OrderStatus::New,
                'payment_status' => PaymentStatus::Unpaid,
                'locale' => $locale,
                'currency' => $cart->currency,
                'shipping_method_id' => $shipping->id,
                'shipping_method_name' => $shipping->name,
                'shipping_type' => $shipping->type,
                'shipping_details' => $shipping->pickup_details,
                'shipping_price' => $totals->shipping,
                'payment_method_id' => $payment->id,
                'payment_method_name' => $payment->name,
                'payment_type' => $payment->type,
                'payment_instructions' => $payment->instructions,
                'payment_fee' => $totals->paymentFee,
                'coupon_code' => $coupon?->code,
                'discount' => $totals->discount,
                'subtotal' => $totals->subtotal,
                'total' => $totals->total(),
                'terms_accepted_at' => now(),
                'legal_consents' => app(LegalPages::class)->consentRecords(),
                'ip_address' => $ipAddress,
            ]);

            foreach ($cart->lines as $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'product_variant_id' => $line->variant?->id,
                    'name' => $line->product->name,
                    'variant_name' => $line->variant?->name(),
                    'sku' => $line->variant?->sku ?? $line->product->sku,
                    'quantity' => $line->quantity,
                    'unit_price' => (int) $line->unitPrice,
                    'total' => $line->total(),
                    'tax_rate' => $this->tax->effectiveRate($line->product->vatRate()),
                ]);
            }

            $order->history()->create(['type' => OrderHistoryEntry::CREATED]);

            return $order;
        });

        // E-maily a ďalšie reakcie až po úspešnom uložení objednávky.
        OrderPlaced::dispatch($order);

        return $order;
    }

    /**
     * Overí kupón ešte raz (zamknutý riadok, aby sa neprekročil limit použití) a započíta použitie.
     */
    private function claimCoupon(?Coupon $coupon, CartSummary $cart): ?Coupon
    {
        if ($coupon === null) {
            return null;
        }

        $locked = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->first();

        if ($locked === null || ($reason = $locked->rejectionReason($cart->subtotal(), $cart->currency)) !== null) {
            throw new CheckoutException($reason ?? __('orders::messages.coupon_invalid'));
        }

        $locked->increment('used_count');

        return $locked;
    }

    /**
     * Odpíše sklad. Riadky produktov sa zamknú, aby dvaja zákazníci nekúpili posledný kus naraz.
     */
    private function reserveStock(CartSummary $cart, string $orderNumber): void
    {
        foreach ($cart->lines as $line) {
            /** @var Product|null $product */
            $product = Product::query()->whereKey($line->product->id)->lockForUpdate()->first();
            $variant = $line->variant
                ? ProductVariant::query()->whereKey($line->variant->id)->lockForUpdate()->first()?->setRelation('product', $product)
                : null;

            $available = $product !== null && ($line->variant
                ? $variant !== null && $variant->isAvailable($line->quantity)
                : $product->isAvailable($line->quantity));

            if (! $available) {
                throw new CheckoutException(__('orders::messages.out_of_stock', ['product' => $line->name()]));
            }

            $this->stock->adjust($product, -$line->quantity, StockMovement::ORDER, $orderNumber, variant: $variant);
        }
    }
}
