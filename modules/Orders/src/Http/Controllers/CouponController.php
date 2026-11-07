<?php

namespace Modules\Orders\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cart\CartManager;
use Modules\Orders\Models\Coupon;

class CouponController extends Controller
{
    public function __construct(private readonly CartManager $cart) {}

    public function apply(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:64']]);
        $summary = $this->cart->summary();
        $coupon = Coupon::findByCode($data['code']);

        if ($coupon === null) {
            return back()->with('error', __('orders::messages.coupon_invalid'));
        }

        if ($reason = $coupon->rejectionReason($summary->subtotal(), $summary->currency)) {
            return back()->with('error', $reason);
        }

        $this->cart->setCouponCode($coupon->code);

        return back()->with('success', __('orders::messages.coupon_applied', ['code' => $coupon->code]));
    }

    public function remove(): RedirectResponse
    {
        $this->cart->setCouponCode(null);

        return back()->with('success', __('orders::messages.coupon_removed'));
    }
}
