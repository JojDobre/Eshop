<?php

namespace Modules\Storefront\Http\Controllers;

use App\Core\Support\Currencies;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

/**
 * Výber meny zákazníkom (prepínač v hlavičke obchodu).
 */
class CurrencyController extends Controller
{
    public function __invoke(Request $request, Currencies $currencies): RedirectResponse
    {
        $currency = strtoupper((string) $request->input('currency'));

        if ($currencies->isEnabled($currency)) {
            $request->session()->put(SetStorefrontLocale::CURRENCY_SESSION, $currency);
        }

        return redirect()->back(fallback: route('storefront.home'));
    }
}
