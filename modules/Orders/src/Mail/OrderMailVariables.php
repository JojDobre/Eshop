<?php

namespace Modules\Orders\Mail;

use App\Core\Support\Price;
use App\Settings\CompanySettings;
use Modules\Orders\Models\Order;
use Modules\Orders\Payments\PaymentGateways;
use Modules\Payments\PaymentType;

/**
 * Premenné pre e-maily o objednávke (hodnoty pre {premenné} v upraviteľných šablónach).
 */
class OrderMailVariables
{
    /** Premenné, ktorých hodnota je hotový Markdown (tabuľky, odkazy). */
    public const RAW = ['order_items', 'payment_details', 'shipping_details', 'order_url', 'admin_url', 'tracking'];

    /**
     * @return array<string, string>
     */
    public static function for(Order $order): array
    {
        $order->loadMissing('items');
        $money = fn (int $amount) => Price::format($amount, $order->currency, $order->locale);

        return [
            'customer_name' => $order->billing_name,
            'customer_email' => $order->email,
            'customer_phone' => (string) $order->phone,
            'customer_note' => (string) $order->customer_note,
            'order_number' => $order->number,
            'order_total' => $money($order->total),
            'order_status' => $order->status->getLabel(),
            'shipping_method' => $order->shipping_method_name,
            'payment_method' => $order->payment_method_name,
            'order_items' => self::itemsTable($order, $money),
            'payment_details' => self::paymentDetails($order, $money),
            'shipping_details' => $order->shipping_details ? '**'.e($order->shipping_method_name)."**  \n".nl2br(e($order->shipping_details)) : '',
            'tracking' => self::tracking($order),
            'order_url' => $order->publicUrl(),
            'admin_url' => url('/admin/orders/'.$order->id),
        ];
    }

    /**
     * Sledovanie zásielok: „Sledovať zásielku 1234 (odkaz)“ pre každú zásielku so sledovacím číslom.
     */
    private static function tracking(Order $order): string
    {
        return $order->shipments()->whereNotNull('tracking_number')->get()
            ->map(function ($shipment) {
                $url = $shipment->trackingUrl();
                $label = __('orders::mail.track_shipment', ['number' => $shipment->tracking_number]);

                return $url ? '['.str_replace(['[', ']'], '', $label).']('.$url.')' : $label;
            })
            ->implode("  \n");
    }

    private static function itemsTable(Order $order, \Closure $money): string
    {
        $rows = ['| '.__('orders::mail.product').' | '.__('orders::mail.quantity').' | '.__('orders::mail.price').' |', '|:--|:-:|--:|'];

        foreach ($order->items as $item) {
            $rows[] = '| '.self::cell($item->fullName()).' | '.$item->quantity.' | '.$money($item->total).' |';
        }

        if ($order->discount > 0) {
            $rows[] = '| '.self::cell(__('orders::mail.discount', ['code' => $order->coupon_code])).' | | −'.$money($order->discount).' |';
        }

        $rows[] = '| '.self::cell($order->shipping_method_name).' | | '.$money($order->shipping_price).' |';

        if ($order->payment_fee > 0) {
            $rows[] = '| '.self::cell($order->payment_method_name).' | | '.$money($order->payment_fee).' |';
        }

        $rows[] = '| **'.__('orders::mail.total').'** | | **'.$money($order->total).'** |';

        return "<x-mail::table>\n".implode("\n", $rows)."\n</x-mail::table>";
    }

    private static function paymentDetails(Order $order, \Closure $money): string
    {
        $lines = [];

        if ($order->payment_type === PaymentType::BankTransfer && ! $order->isPaid()) {
            $company = app(CompanySettings::class);
            $lines[] = '**'.__('orders::mail.bank.heading').'**';

            if ($company->iban) {
                $lines[] = __('orders::mail.bank.account').': **'.e($company->iban).'**'.($company->swift ? ' (SWIFT '.e($company->swift).')' : '');
            }

            $lines[] = __('orders::mail.bank.variable_symbol').': **'.$order->variableSymbol().'**';
            $lines[] = __('orders::mail.bank.amount').': **'.$money($order->total).'**';
        }

        if (app(PaymentGateways::class)->forPayableOrder($order) !== null) {
            $lines[] = '['.__('orders::mail.pay_online').']('.route('orders.pay', $order->token).')';
        }

        if ($order->payment_instructions) {
            $lines[] = e($order->payment_instructions);
        }

        return implode("  \n", $lines);
    }

    private static function cell(string $text): string
    {
        return str_replace('|', '\|', e($text));
    }
}
