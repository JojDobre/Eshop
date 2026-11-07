<?php

namespace Modules\Stripe\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Actions\SetPaymentStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\PaymentStatus;
use Modules\Stripe\Models\StripePayment;

/**
 * Spracuje reláciu Stripe Checkout: ak je zaplatená a suma sedí, označí objednávku ako zaplatenú.
 * Volá sa z webhooku aj po návrate zákazníka (webhook nemusí byť na hostingu nastavený).
 */
class ConfirmStripeSession
{
    public function __construct(private readonly SetPaymentStatus $setPaymentStatus) {}

    /**
     * @param  array<string, mixed>  $session
     */
    public function handle(array $session): bool
    {
        $payment = StripePayment::query()->where('session_id', (string) ($session['id'] ?? ''))->first();

        if ($payment === null || ($session['payment_status'] ?? null) !== 'paid') {
            return false;
        }

        if ((int) ($session['amount_total'] ?? -1) !== $payment->amount || strtoupper((string) ($session['currency'] ?? '')) !== $payment->currency) {
            Log::warning('Stripe session amount does not match the order.', ['session' => $payment->session_id]);

            return false;
        }

        DB::transaction(function () use ($payment, $session): void {
            // Zámok proti súbežnému spracovaniu webhooku a návratu zákazníka.
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

            $payment->update([
                'status' => StripePayment::PAID,
                'payment_intent' => is_string($session['payment_intent'] ?? null) ? $session['payment_intent'] : $payment->payment_intent,
                'paid_at' => $payment->paid_at ?? now(),
            ]);

            if (! $order->isPaid()) {
                $this->setPaymentStatus->handle($order, PaymentStatus::Paid);
            }
        });

        return true;
    }
}
