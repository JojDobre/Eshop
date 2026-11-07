<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Orders\CheckoutFields;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $countries = config('eshop.countries');
        $shipping = $this->shippingMethod();
        $needsAddress = $shipping?->type->requiresShippingAddress() ?? false;
        $differentAddress = $needsAddress && $this->boolean('ship_to_different_address');

        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[0-9 +()\/-]{6,}$/'],

            'billing_name' => ['required', 'string', 'max:120'],
            'billing_street' => ['required', 'string', 'max:160'],
            'billing_city' => ['required', 'string', 'max:120'],
            'billing_zip' => ['required', 'string', 'max:16'],
            'billing_country' => ['required', Rule::in($countries)],

            'is_company' => ['nullable', 'boolean'],
            'billing_company' => [Rule::requiredIf($this->boolean('is_company')), 'nullable', 'string', 'max:160'],
            'billing_company_id' => [Rule::requiredIf($this->boolean('is_company')), 'nullable', 'string', 'max:32'],
            'billing_tax_id' => ['nullable', 'string', 'max:32'],
            'billing_vat_id' => ['nullable', 'string', 'max:32'],

            'ship_to_different_address' => ['nullable', 'boolean'],
            'shipping_name' => [Rule::requiredIf($differentAddress), 'nullable', 'string', 'max:120'],
            'shipping_street' => [Rule::requiredIf($differentAddress), 'nullable', 'string', 'max:160'],
            'shipping_city' => [Rule::requiredIf($differentAddress), 'nullable', 'string', 'max:120'],
            'shipping_zip' => [Rule::requiredIf($differentAddress), 'nullable', 'string', 'max:16'],
            'shipping_country' => [Rule::requiredIf($differentAddress), 'nullable', Rule::in($countries)],

            'shipping_method_id' => ['required', Rule::exists('shipping_methods', 'id')->where('is_active', true)],
            'payment_method_id' => ['required', Rule::exists('payment_methods', 'id')->where('is_active', true)],

            'customer_note' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
            ...CheckoutFields::rules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return trans('orders::checkout.attributes');
    }

    public function shippingMethod(): ?ShippingMethod
    {
        return ShippingMethod::query()->active()->find($this->integer('shipping_method_id'));
    }

    public function paymentMethod(): ?PaymentMethod
    {
        return PaymentMethod::query()->active()->with('shippingMethods')->find($this->integer('payment_method_id'));
    }

    /**
     * Údaje zákazníka pripravené na uloženie do objednávky.
     *
     * @return array<string, mixed>
     */
    public function customerData(): array
    {
        $data = $this->validated();
        $isCompany = (bool) ($data['is_company'] ?? false);
        $different = (bool) ($data['ship_to_different_address'] ?? false);

        return [
            'email' => mb_strtolower(trim($data['email'])),
            'phone' => trim($data['phone']),
            'billing_name' => trim($data['billing_name']),
            'billing_company' => $isCompany ? trim((string) $data['billing_company']) : null,
            'billing_company_id' => $isCompany ? trim((string) $data['billing_company_id']) : null,
            'billing_tax_id' => $isCompany ? (trim((string) ($data['billing_tax_id'] ?? '')) ?: null) : null,
            'billing_vat_id' => $isCompany ? (trim((string) ($data['billing_vat_id'] ?? '')) ?: null) : null,
            'billing_street' => trim($data['billing_street']),
            'billing_city' => trim($data['billing_city']),
            'billing_zip' => trim($data['billing_zip']),
            'billing_country' => $data['billing_country'],
            'shipping_name' => $different ? trim((string) $data['shipping_name']) : trim($data['billing_name']),
            'shipping_street' => $different ? trim((string) $data['shipping_street']) : trim($data['billing_street']),
            'shipping_city' => $different ? trim((string) $data['shipping_city']) : trim($data['billing_city']),
            'shipping_zip' => $different ? trim((string) $data['shipping_zip']) : trim($data['billing_zip']),
            'shipping_country' => $different ? $data['shipping_country'] : $data['billing_country'],
            'customer_note' => trim((string) ($data['customer_note'] ?? '')) ?: null,
        ];
    }
}
