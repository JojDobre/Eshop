<?php

namespace Modules\Orders\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cart\CartSummary;
use Modules\Catalog\Models\Product;
use Modules\Orders\CheckoutException;
use Modules\Orders\CheckoutTotals;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderHistoryEntry;
use Modules\Orders\NumberSequence;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;

/**
 * Vytvorí objednávku z košíka: overí dostupnosť, odpíše sklad a uloží kópiu všetkých údajov.
 */
class PlaceOrder
{
    public function __construct(private readonly NumberSequence $numbers) {}

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
    ): Order {
        if (! $cart->canCheckout()) {
            throw new CheckoutException(__('orders::messages.cart_changed'));
        }

        if (! $shipping->is_active || ! $payment->is_active || ! $payment->isAvailableFor($shipping)) {
            throw new CheckoutException(__('orders::messages.payment_not_available'));
        }

        $order = DB::transaction(function () use ($cart, $shipping, $payment, $customer, $locale, $ipAddress): Order {
            $this->reserveStock($cart);

            $totals = CheckoutTotals::calculate($cart, $shipping, $payment);
            $needsAddress = $shipping->type->requiresShippingAddress();

            $order = Order::query()->create([
                ...$customer,
                ...($needsAddress ? [] : [
                    'shipping_name' => null, 'shipping_street' => null, 'shipping_city' => null,
                    'shipping_zip' => null, 'shipping_country' => null,
                ]),
                'number' => $this->numbers->nextOrderNumber(),
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
                'subtotal' => $totals->subtotal,
                'total' => $totals->total(),
                'terms_accepted_at' => now(),
                'ip_address' => $ipAddress,
            ]);

            foreach ($cart->lines as $line) {
                $order->items()->create([
                    'product_id' => $line->product->id,
                    'name' => $line->product->name,
                    'sku' => $line->product->sku,
                    'quantity' => $line->quantity,
                    'unit_price' => (int) $line->unitPrice,
                    'total' => $line->total(),
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
     * Odpíše sklad. Riadky produktov sa zamknú, aby dvaja zákazníci nekúpili posledný kus naraz.
     */
    private function reserveStock(CartSummary $cart): void
    {
        foreach ($cart->lines as $line) {
            /** @var Product|null $product */
            $product = Product::query()->whereKey($line->product->id)->lockForUpdate()->first();

            if ($product === null || ! $product->isAvailable($line->quantity)) {
                throw new CheckoutException(__('orders::messages.out_of_stock', ['product' => $line->product->name]));
            }

            if ($product->track_stock) {
                $product->decrement('stock_quantity', $line->quantity);
            }
        }
    }
}
