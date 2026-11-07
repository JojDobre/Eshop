<?php

namespace Modules\Customers\Http\Controllers;

use App\Core\Content\ContentLocale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Modules\Customers\Actions\RegisterCustomer;
use Modules\Customers\Models\Customer;
use Modules\Storefront\Pages\LegalPages;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('theme::templates.account.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Ochrana proti hádaniu hesla: 5 pokusov na e-mail a IP adresu za minútu.
        $key = 'customer-login:'.Str::lower($credentials['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => __('customers::messages.throttled', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        if (! auth('customer')->attempt(['email' => Str::lower(trim($credentials['email'])), 'password' => $credentials['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages(['email' => __('customers::messages.failed')]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        auth('customer')->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('customer.account'));
    }

    public function showRegister(): View
    {
        return view('theme::templates.account.register', ['legalPages' => app(LegalPages::class)]);
    }

    public function register(Request $request, RegisterCustomer $register): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', function (string $attribute, mixed $value, \Closure $fail): void {
                if (Customer::query()->where('email', Str::lower(trim((string) $value)))->exists()) {
                    $fail(__('customers::messages.email_taken'));
                }
            }],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ]);

        $customer = $register->handle($data, app(ContentLocale::class)->current());

        auth('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->intended(route('customer.account'))->with('success', __('customers::messages.registered'));
    }

    public function logout(Request $request): RedirectResponse
    {
        auth('customer')->logout();
        // Košík a ostatné údaje relácie ostanú, mení sa iba prihlásenie.
        $request->session()->regenerateToken();
        $request->session()->migrate(true);

        return redirect()->route('storefront.home');
    }
}
