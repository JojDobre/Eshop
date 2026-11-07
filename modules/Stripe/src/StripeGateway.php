<?php

namespace Modules\Stripe;

use Modules\Orders\Models\Order;
use Modules\Orders\Payments\PaymentGateway;
use Modules\Payments\PaymentType;
use Modules\Stripe\Models\StripePayment;

/**
 * Platba kartou cez Stripe Checkout (platobná stránka Stripe, obchod údaje o karte nevidí).
 */
class StripeGateway implements PaymentGateway
{
    // Nastavenia a API sa načítajú až pri použití (brána sa registruje pri štarte, keď ešte nemusí existovať databáza).

    public function type(): PaymentType
    {
        return PaymentType::Card;
    }

    public function isAvailable(): bool
    {
        try {
            return app(StripeSettings::class)->isConfigured();
        } catch (\Throwable) {
            return false;
        }
    }

    public function startPayment(Order $order): string
    {
        $session = app(StripeApi::class)->createCheckoutSession([
            'mode' => 'payment',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => strtolower($order->currency),
                    'unit_amount' => $order->total,
                    'product_data' => ['name' => __('stripe::messages.line_item', ['number' => $order->number], $order->locale)],
                ],
            ]],
            'customer_email' => $order->email,
            'client_reference_id' => $order->number,
            'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->number],
            'payment_intent_data' => ['metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->number]],
            'locale' => in_array($order->locale, ['sk', 'cs', 'en'], true) ? $order->locale : 'auto',
            'success_url' => route('stripe.return', $order->token).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('stripe.cancel', $order->token),
        ]);

        StripePayment::query()->create([
            'order_id' => $order->id,
            'session_id' => $session['id'],
            'amount' => $order->total,
            'currency' => $order->currency,
        ]);

        return (string) $session['url'];
    }
}
