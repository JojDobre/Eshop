<?php

namespace Modules\Orders\Http\Controllers;

use App\Settings\CompanySettings;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Orders\Models\Order;
use Modules\Orders\Payments\PaymentGateways;

/**
 * Stránka objednávky pre zákazníka (prístup cez náhodný kľúč z e-mailu, bez prihlásenia).
 */
class OrderController extends Controller
{
    public function __invoke(string $token): View
    {
        $order = Order::query()->where('token', $token)->with('items')->firstOrFail();

        return view('theme::templates.order', [
            'order' => $order,
            'company' => app(CompanySettings::class),
            'justPlaced' => (bool) session('order_placed'),
            'canPayOnline' => app(PaymentGateways::class)->forPayableOrder($order) !== null,
        ]);
    }
}
