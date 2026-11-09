<?php

namespace Modules\Epr;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Orders\OrderStatus;

/**
 * Výkaz obalov uvedených na trh: obaly predaných kusov a obaly zásielok za obdobie (bez zrušených objednávok).
 */
class Packaging
{
    /** Materiály podľa výkazov (SK zákon 79/2015, CZ zákon 545/2020). */
    public const MATERIALS = ['paper', 'plastic', 'glass', 'metal', 'aluminium', 'wood', 'composite', 'other'];

    public function __construct(private readonly EprSettings $settings) {}

    /**
     * @return array{materials: array<string, array{products: int, shipments: int, total: int}>, orders: int}
     */
    public function report(Carbon $from, Carbon $to): array
    {
        $orders = DB::table('orders')->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
        $orderCount = (clone $orders)->count();
        $materials = array_fill_keys(self::MATERIALS, ['products' => 0, 'shipments' => 0, 'total' => 0]);

        DB::table('order_items')->join('products', 'products.id', '=', 'order_items.product_id')
            ->whereIn('order_items.order_id', (clone $orders)->select('id'))
            ->whereNotNull('products.packaging')
            ->select('order_items.quantity', 'products.packaging')
            ->orderBy('order_items.id')
            ->chunk(500, function ($rows) use (&$materials): void {
                foreach ($rows as $row) {
                    foreach ((array) json_decode((string) $row->packaging, true) as $material => $grams) {
                        if (isset($materials[$material])) {
                            $materials[$material]['products'] += (int) round((float) $grams * $row->quantity);
                        }
                    }
                }
            });

        foreach ($this->settings->shipment_packaging as $material => $grams) {
            if (isset($materials[$material])) {
                $materials[$material]['shipments'] = (int) round((float) $grams * $orderCount);
            }
        }

        foreach ($materials as $material => $values) {
            $materials[$material]['total'] = $values['products'] + $values['shipments'];
        }

        return ['materials' => $materials, 'orders' => $orderCount];
    }
}
