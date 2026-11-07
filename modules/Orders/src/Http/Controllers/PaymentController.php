<?php

namespace Modules\Orders\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Orders\Models\Order;
use Modules\Orders\Payments\PaymentGateways;
use Throwable;

/**
 * Presmerovanie na platobnú bránu (po objednávke alebo opakovaná platba zo stránky objednávky).
 */
class PaymentController extends Controller
{
    public function __invoke(string $token, PaymentGateways $gateways): RedirectResponse
    {
        $order = Order::query()->where('token', $token)->firstOrFail();
        $gateway = $gateways->forPayableOrder($order);

        if ($gateway === null) {
            return redirect()->to($order->publicUrl());
        }

        try {
            return redirect()->away($gateway->startPayment($order));
        } catch (Throwable $e) {
            Log::error('Payment gateway failed to start a payment.', ['order' => $order->number, 'exception' => $e]);

            return redirect()->to($order->publicUrl())->with('payment_error', __('orders::messages.payment_start_failed'));
        }
    }
}
