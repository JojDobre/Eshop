<?php

namespace Modules\Stripe\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Models\Order;
use Modules\Stripe\Actions\ConfirmStripeSession;
use Modules\Stripe\Models\StripePayment;
use Modules\Stripe\StripeApi;
use Throwable;

/**
 * Návrat zákazníka zo Stripe Checkout.
 */
class StripeReturnController extends Controller
{
    public function success(Request $request, string $token, StripeApi $api, ConfirmStripeSession $confirm): RedirectResponse
    {
        $order = Order::query()->where('token', $token)->firstOrFail();
        $sessionId = (string) $request->query('session_id');
        $known = StripePayment::query()->where('order_id', $order->id)->where('session_id', $sessionId)->exists();

        if ($known && ! $order->isPaid()) {
            try {
                $confirm->handle($api->retrieveCheckoutSession($sessionId));
            } catch (Throwable $e) {
                Log::error('Stripe session could not be verified.', ['order' => $order->number, 'exception' => $e]);
            }
        }

        // Ak platba ešte nie je potvrdená (napr. oneskorená platba), potvrdí ju webhook.
        return redirect()->to($order->publicUrl())->with('payment_result', $order->fresh()->isPaid() ? 'paid' : null);
    }

    public function cancel(string $token): RedirectResponse
    {
        $order = Order::query()->where('token', $token)->firstOrFail();

        return redirect()->to($order->publicUrl())->with('payment_result', 'cancelled');
    }
}
