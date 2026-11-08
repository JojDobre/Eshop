<?php

namespace Modules\Orders\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cart\CartManager;
use Modules\Orders\GiftVouchers;
use Modules\Orders\Models\GiftVoucher;

class GiftVoucherController extends Controller
{
    public function __construct(private readonly CartManager $cart, private readonly GiftVouchers $vouchers) {}

    public function apply(Request $request): RedirectResponse
    {
        abort_unless($this->vouchers->enabled(), 404);

        $data = $request->validate(['code' => ['required', 'string', 'max:40']]);
        $voucher = GiftVoucher::findByCode($data['code']);

        if ($voucher === null) {
            return back()->with('error', __('orders::messages.voucher_invalid'));
        }

        if ($reason = $voucher->rejectionReason($this->cart->summary()->currency)) {
            return back()->with('error', $reason);
        }

        $this->vouchers->apply($voucher->code);

        return back()->with('success', __('orders::messages.voucher_applied', ['balance' => format_price($voucher->balance, $voucher->currency)]));
    }

    public function remove(): RedirectResponse
    {
        $this->vouchers->remove();

        return back()->with('success', __('orders::messages.voucher_removed'));
    }
}
