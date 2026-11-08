<?php

namespace Modules\Orders\Actions;

use App\Core\Support\Currencies;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cart\CartSummary;
use Modules\Catalog\InsufficientStock;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\StockMovement;
use Modules\Catalog\StockService;
use Modules\Orders\CheckoutException;
use Modules\Orders\CheckoutTotals;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Models\Coupon;
use Modules\Orders\Models\GiftVoucher;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderHistoryEntry;
use Modules\Orders\NumberSequence;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Storefront\Pages\LegalPages;
use Modules\Tax\TaxCalculator;
use Modules\Tax\TaxRules;

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
        ?GiftVoucher $voucher = null,
    ): Order {
        if (! $cart->canCheckout()) {
            throw new CheckoutException(__('orders::messages.cart_changed'));
        }

        if (! $shipping->is_active || ! $payment->is_active || ! $payment->isAvailableFor($shipping)) {
            throw new CheckoutException(__('orders::messages.payment_not_available'));
        }

        $order = DB::transaction(function () use ($cart, $shipping, $payment, $customer, $locale, $ipAddress, $coupon, $voucher): Order {
            $number = $this->numbers->nextOrderNumber();
            $allocations = $this->reserveStock($cart, $number);
            $coupon = $this->claimCoupon($coupon, $cart);

            $needsAddress = $shipping->type->requiresShippingAddress();
            // DPH podľa krajiny doručenia (pri osobnom odbere sa tovar predáva v krajine obchodu).
            $taxContext = app(TaxRules::class)->context(
                $needsAddress ? ($customer['shipping_country'] ?? null) : null,
                filled($customer['billing_company'] ?? null),
                $customer['billing_vat_id'] ?? null,
            );
            $voucher = $this->lockVoucher($voucher, $cart);
            $totals = CheckoutTotals::calculate($cart, $shipping, $payment, $coupon, $taxContext, $voucher);
            $serviceRate = (float) $totals->serviceRate;

            $order = Order::query()->create([
                ...$customer,
                ...($needsAddress ? [] : [
                    'shipping_name' => null, 'shipping_street' => null, 'shipping_city' => null,
                    'shipping_zip' => null, 'shipping_country' => null,
                ]),
                'number' => $number,
                'vat_payer' => $this->tax->isVatPayer(),
                'tax_mode' => $taxContext->mode,
                'tax_country' => $taxContext->country,
                'shipping_tax_rate' => $serviceRate,
                'payment_tax_rate' => $serviceRate,
                'token' => Str::random(48),
                'status' => OrderStatus::New,
                'payment_status' => PaymentStatus::Unpaid,
                'locale' => $locale,
                'currency' => $cart->currency,
                'exchange_rate' => app(Currencies::class)->rate($cart->currency),
                'shipping_method_id' => $shipping->id,
                'shipping_method_name' => $shipping->name,
                'shipping_type' => $shipping->type,
                // Výdajné miesto kuriéra, inak údaje o osobnom odbere zo spôsobu dopravy.
                'shipping_details' => ! empty($customer['pickup_point'])
                    ? trim(($customer['pickup_point']['name'] ?? '')."\n".implode(', ', array_filter([$customer['pickup_point']['street'] ?? null, trim(($customer['pickup_point']['zip'] ?? '').' '.($customer['pickup_point']['city'] ?? ''))])))
                    : $shipping->pickup_details,
                'shipping_cost' => $shipping->cost === null ? null : app(Currencies::class)->convert($shipping->cost, $shipping->currency, $cart->currency),
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
                'voucher_code' => $totals->giftVoucher?->code,
                'voucher_amount' => $totals->voucher,
                'terms_accepted_at' => now(),
                'legal_consents' => app(LegalPages::class)->consentRecords(),
                'ip_address' => $ipAddress,
            ]);

            foreach ($cart->lines as $index => $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'product_variant_id' => $line->variant?->id,
                    'name' => $line->product->name,
                    'variant_name' => $line->variant?->name(),
                    'allocations' => $allocations[$index] ?? null,
                    'is_preorder' => collect($allocations[$index] ?? [])->contains(fn (array $allocation) => ! empty($allocation['preorder'])),
                    'addons' => $line->addons === [] ? null : array_map(fn (array $addon) => ['name' => $addon['name'], 'price' => $addon['price'], 'text' => $addon['text']], $line->addons),
                    'sku' => $line->variant?->sku ?? $line->product->sku,
                    'quantity' => $line->quantity,
                    'unit_price' => $totals->lines[$index]['unit'],
                    'total' => $totals->lines[$index]['total'],
                    'tax_rate' => $totals->lines[$index]['rate'],
                ]);
            }

            if ($totals->giftVoucher !== null) {
                $totals->giftVoucher->decrement('balance', $totals->voucher);
                $totals->giftVoucher->usages()->create(['order_id' => $order->id, 'amount' => $totals->voucher]);
            }

            $order->history()->create(['type' => OrderHistoryEntry::CREATED]);

            return $order;
        });

        // Objednávka celá uhradená poukážkou je zaplatená (ešte pred potvrdzujúcim e-mailom).
        if ($order->voucher_amount > 0 && $order->amountDue() === 0) {
            app(SetPaymentStatus::class)->handle($order, PaymentStatus::Paid);
        }

        // E-maily a ďalšie reakcie až po úspešnom uložení objednávky.
        OrderPlaced::dispatch($order);

        return $order;
    }

    /**
     * Poukážka zamknutá na čas objednávky (aby sa jej zostatok nečerpal dvakrát naraz).
     */
    private function lockVoucher(?GiftVoucher $voucher, CartSummary $cart): ?GiftVoucher
    {
        if ($voucher === null) {
            return null;
        }

        $locked = GiftVoucher::query()->whereKey($voucher->id)->lockForUpdate()->first();

        if ($locked === null || ($reason = $locked->rejectionReason($cart->currency)) !== null) {
            throw new CheckoutException($reason ?? __('orders::messages.voucher_invalid'));
        }

        return $locked;
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
    /**
     * Odpíše sklad pre všetky položky (so zámkom riadkov) a vráti, odkiaľ sa tovar vydal.
     *
     * @return array<int, list<array{product_id: int, variant_id: int|null, quantity: int}>> podľa indexu položky
     */
    private function reserveStock(CartSummary $cart, string $orderNumber): array
    {
        $allocations = [];

        foreach ($cart->lines as $index => $line) {
            /** @var Product|null $product */
            $product = Product::query()->whereKey($line->product->id)->lockForUpdate()->first();
            $variant = $line->variant
                ? ProductVariant::query()->whereKey($line->variant->id)->lockForUpdate()->first()?->setRelation('product', $product)
                : null;

            if ($product === null || ($line->variant && $variant === null)) {
                throw new CheckoutException(__('orders::messages.out_of_stock', ['product' => $line->name()]));
            }

            try {
                $allocations[$index] = $this->stock->take($product, $variant, $line->quantity, StockMovement::ORDER, $orderNumber);
            } catch (InsufficientStock) {
                throw new CheckoutException(__('orders::messages.out_of_stock', ['product' => $line->name()]));
            }
        }

        return $allocations;
    }
}
