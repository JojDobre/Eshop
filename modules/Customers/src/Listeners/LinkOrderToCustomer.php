<?php

namespace Modules\Customers\Listeners;

use Modules\Customers\Models\Customer;
use Modules\Orders\Events\OrderPlaced;

/**
 * Objednávku prihláseného zákazníka priradí k jeho účtu a zapamätá si adresy pre ďalší nákup.
 */
class LinkOrderToCustomer
{
    public function handle(OrderPlaced $event): void
    {
        $customer = auth('customer')->user();

        if (! $customer instanceof Customer) {
            return;
        }

        $order = $event->order;
        $order->forceFill(['customer_id' => $customer->id])->saveQuietly();

        $customer->forceFill([
            'phone' => $customer->phone ?: $order->phone,
            'billing_address' => array_filter([
                'name' => $order->billing_name, 'company' => $order->billing_company, 'company_id' => $order->billing_company_id,
                'tax_id' => $order->billing_tax_id, 'vat_id' => $order->billing_vat_id, 'street' => $order->billing_street,
                'city' => $order->billing_city, 'zip' => $order->billing_zip, 'country' => $order->billing_country,
            ], fn ($value) => filled($value)),
            'shipping_address' => array_filter([
                'name' => $order->shipping_name, 'street' => $order->shipping_street, 'city' => $order->shipping_city,
                'zip' => $order->shipping_zip, 'country' => $order->shipping_country,
            ], fn ($value) => filled($value)),
        ])->save();
    }
}
