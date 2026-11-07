<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Modules\Customers\Models\Customer;

class PasswordResetController extends Controller
{
    public function showRequest(): View
    {
        return view('theme::templates.account.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('customers')->sendResetLink(['email' => Str::lower(trim((string) $request->input('email')))]);

        // Rovnaká odpoveď bez ohľadu na to, či účet existuje (nedá sa zistiť, kto je zákazník).
        return back()->with('success', __('customers::messages.reset_link_sent'));
    }

    public function showReset(Request $request, string $token): View
    {
        return view('theme::templates.account.reset-password', ['token' => $token, 'email' => (string) $request->query('email')]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::broker('customers')->reset(
            ['email' => Str::lower(trim((string) $request->input('email'))), 'password' => $request->input('password'), 'token' => $request->input('token')],
            function (Customer $customer, string $password): void {
                $customer->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __('customers::messages.reset_invalid')]);
        }

        return redirect()->route('customer.login')->with('success', __('customers::messages.password_reset'));
    }
}
