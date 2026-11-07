<?php

namespace Modules\Orders;

use Modules\Cart\CartManager;
use Modules\Cart\CartSummary;
use Modules\Orders\Models\Coupon;

/**
 * Kupón uložený pri košíku – vráti ho iba vtedy, ak sa na aktuálny košík dá použiť.
 */
class Coupons
{
    public function __construct(private readonly CartManager $cart) {}

    public function forCart(CartSummary $summary): ?Coupon
    {
        $coupon = Coupon::findByCode($this->cart->couponCode());

        return $coupon !== null && $coupon->rejectionReason($summary->subtotal(), $summary->currency) === null ? $coupon : null;
    }
}
