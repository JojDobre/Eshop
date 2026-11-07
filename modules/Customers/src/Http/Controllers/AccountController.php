<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Modules\Customers\Actions\DeleteCustomer;
use Modules\Customers\Models\Customer;

class AccountController extends Controller
{
    public function orders(): View
    {
        return view('theme::templates.account.orders', [
            'orders' => $this->customer()->orders()->paginate(20),
        ]);
    }

    public function profile(): View
    {
        return view('theme::templates.account.profile', ['account' => $this->customer()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = $this->customer();
        $countries = config('eshop.countries');
        $address = fn (string $prefix, bool $company = false) => [
            $prefix.'.name' => ['nullable', 'string', 'max:120'],
            $prefix.'.street' => ['nullable', 'string', 'max:160'],
            $prefix.'.city' => ['nullable', 'string', 'max:120'],
            $prefix.'.zip' => ['nullable', 'string', 'max:16'],
            $prefix.'.country' => ['nullable', Rule::in($countries)],
            ...($company ? [
                $prefix.'.company' => ['nullable', 'string', 'max:160'],
                $prefix.'.company_id' => ['nullable', 'string', 'max:32'],
                $prefix.'.tax_id' => ['nullable', 'string', 'max:32'],
                $prefix.'.vat_id' => ['nullable', 'string', 'max:32'],
            ] : []),
        ];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', function (string $attribute, mixed $value, \Closure $fail) use ($customer): void {
                if (Customer::query()->where('email', Str::lower(trim((string) $value)))->whereKeyNot($customer->id)->exists()) {
                    $fail(__('customers::messages.email_taken'));
                }
            }],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9 +()\/-]{6,}$/'],
            ...$address('billing_address', true),
            ...$address('shipping_address'),
        ]);

        $clean = fn (?array $values) => array_filter(array_map(fn ($value) => is_string($value) ? trim($value) : $value, $values ?? []), fn ($value) => filled($value));

        $customer->update([
            'name' => trim($data['name']),
            'email' => $data['email'],
            'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
            'billing_address' => $clean($data['billing_address'] ?? []),
            'shipping_address' => $clean($data['shipping_address'] ?? []),
        ]);

        return back()->with('success', __('customers::messages.profile_saved'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $this->customer()->update(['password' => $request->input('password')]);
        auth('customer')->logoutOtherDevices($request->input('password'));

        return back()->with('success', __('customers::messages.password_changed'));
    }

    public function destroy(Request $request, DeleteCustomer $delete): RedirectResponse
    {
        $request->validateWithBag('delete', ['delete_password' => ['required', 'current_password:customer']]);

        $customer = $this->customer();
        auth('customer')->logout();
        $delete->handle($customer);
        $request->session()->regenerateToken();

        return redirect()->route('storefront.home')->with('success', __('customers::messages.deleted'));
    }

    private function customer(): Customer
    {
        /** @var Customer $customer */
        $customer = auth('customer')->user();

        return $customer;
    }
}
