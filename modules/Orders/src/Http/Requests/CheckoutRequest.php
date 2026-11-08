<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Orders\CheckoutFields;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\PickupPoint;
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

            // Výdajné miesto (pri doprave na výdajné miesto alebo do boxu).
            'pickup_point' => [Rule::requiredIf((bool) $shipping?->type->requiresPickupPoint()), 'nullable', 'array'],
            'pickup_point.code' => [Rule::requiredIf((bool) $shipping?->type->requiresPickupPoint()), 'nullable', 'string', 'max:64'],
            'pickup_point.name' => ['nullable', 'string', 'max:255'],
            'pickup_point.street' => ['nullable', 'string', 'max:255'],
            'pickup_point.city' => ['nullable', 'string', 'max:120'],
            'pickup_point.zip' => ['nullable', 'string', 'max:16'],
            'pickup_point.country' => ['nullable', 'string', 'size:2'],
            'pickup_point.carrier_code' => ['nullable', 'string', 'max:64'],

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

    /**
     * Doplnkové kontroly: PSČ pre vlastný rozvoz a platnosť výdajného miesta.
     */
    public function after(): array
    {
        return [function ($validator): void {
            $shipping = $this->shippingMethod();

            if ($shipping === null) {
                return;
            }

            $zip = $this->boolean('ship_to_different_address') ? $this->input('shipping_zip') : $this->input('billing_zip');

            if ($shipping->type->requiresShippingAddress() && ! $shipping->deliversToZip(is_string($zip) ? $zip : null)) {
                $validator->errors()->add('shipping_method_id', __('orders::messages.zip_not_served'));
            }

            if ($shipping->type->requiresPickupPoint() && filled($this->input('pickup_point.code')) && $this->pickupPoint() === null) {
                $validator->errors()->add('pickup_point', __('orders::messages.pickup_point_invalid'));
            }
        }];
    }

    /**
     * Zvolené výdajné miesto pripravené na uloženie. Pri kuriéroch so zoznamom v obchode sa údaje
     * vezmú zo zoznamu (nie od prehliadača), pri vlastnom okne kuriéra z jeho výberu.
     *
     * @return array<string, string|null>|null
     */
    public function pickupPoint(): ?array
    {
        $shipping = $this->shippingMethod();
        $input = (array) $this->input('pickup_point', []);
        $code = trim((string) ($input['code'] ?? ''));

        if ($shipping === null || ! $shipping->type->requiresPickupPoint() || $code === '') {
            return null;
        }

        $selector = $shipping->carrierService()?->pickupSelector($shipping);

        if (! empty($selector['list'])) {
            $point = PickupPoint::query()->where('carrier', $shipping->carrier)->where('code', $code)->where('is_active', true)->first();

            return $point?->toOrderData();
        }

        $clean = fn (string $key, int $max) => filled($input[$key] ?? null) ? mb_substr(trim(strip_tags((string) $input[$key])), 0, $max) : null;

        return array_filter([
            'code' => mb_substr($code, 0, 64),
            'name' => $clean('name', 255) ?? $code,
            'street' => $clean('street', 255),
            'city' => $clean('city', 120),
            'zip' => $clean('zip', 16),
            'country' => ($country = $clean('country', 2)) ? strtoupper($country) : null,
            'carrier_code' => $clean('carrier_code', 64),
        ], fn ($value) => $value !== null);
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
            'pickup_point' => $this->pickupPoint(),
        ];
    }
}
