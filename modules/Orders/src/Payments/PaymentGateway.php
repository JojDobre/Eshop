<?php

namespace Modules\Orders\Payments;

use Modules\Orders\Models\Order;
use Modules\Payments\PaymentType;

/**
 * Platobná brána (napr. Stripe). Modul brány ju zaregistruje v PaymentGateways.
 */
interface PaymentGateway
{
    /** Typ platby, ktorý brána spracúva. */
    public function type(): PaymentType;

    /** Je brána nastavená (kľúče zadané, zapnutá)? */
    public function isAvailable(): bool;

    /**
     * Začne platbu a vráti adresu, na ktorú sa zákazník presmeruje.
     *
     * @throws \Throwable pri chybe brány
     */
    public function startPayment(Order $order): string;
}
