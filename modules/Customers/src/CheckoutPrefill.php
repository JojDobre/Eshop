<?php

namespace Modules\Customers;

use Modules\Customers\Models\Customer;

/**
 * Hodnoty polí pokladne z uložených údajov zákazníka.
 */
final class CheckoutPrefill
{
    /**
     * @return array<string, string>
     */
    public static function for(Customer $customer): array
    {
        $prefill = ['email' => $customer->email, 'phone' => (string) $customer->phone];

        foreach ((array) $customer->billing_address as $field => $value) {
            $prefill['billing_'.$field] = (string) $value;
        }

        $prefill['billing_name'] = ($prefill['billing_name'] ?? '') ?: $customer->name;
        $shipping = (array) $customer->shipping_address;

        // Doručovacia adresa sa vyplní iba vtedy, keď sa líši od fakturačnej.
        if (self::differs($customer->billing_address ?? [], $shipping)) {
            $prefill['ship_to_different_address'] = '1';

            foreach ($shipping as $field => $value) {
                $prefill['shipping_'.$field] = (string) $value;
            }
        }

        if (filled($prefill['billing_company'] ?? null)) {
            $prefill['is_company'] = '1';
        }

        return $prefill;
    }

    /**
     * @param  array<string, mixed>  $billing
     * @param  array<string, mixed>  $shipping
     */
    private static function differs(array $billing, array $shipping): bool
    {
        foreach (['name', 'street', 'city', 'zip', 'country'] as $field) {
            if (filled($shipping[$field] ?? null) && ($shipping[$field] ?? null) !== ($billing[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
