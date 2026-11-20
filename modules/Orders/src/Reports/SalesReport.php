<?php

namespace Modules\Orders\Reports;

use Brick\Money\Currency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;

/**
 * Prehľady predaja pre nástenku. Tržby sú v predvolenej mene obchodu (objednávky v iných menách
 * sa prepočítajú kurzom z času nákupu), zrušené objednávky sa nerátajú.
 */
class SalesReport
{
    /**
     * Denné tržby (v najmenších jednotkách meny) a počty objednávok za posledných N dní vrátane dneška.
     *
     * @return array{dates: list<Carbon>, revenue: list<int>, orders: list<int>}
     */
    public function daily(int $days, string $currency): array
    {
        $from = today()->subDays($days - 1);
        $digits = fn (string $code) => Currency::of($code)->getDefaultFractionDigits();

        $rows = Order::query()->counted()->where('created_at', '>=', $from)->toBase()
            ->selectRaw('DATE(created_at) as day, currency, COUNT(*) as orders, SUM(total / exchange_rate) as amount')
            ->groupBy(DB::raw('DATE(created_at)'), 'currency')
            ->get();

        $revenue = [];
        $orders = [];

        foreach ($rows as $row) {
            $day = substr((string) $row->day, 0, 10);
            $revenue[$day] = ($revenue[$day] ?? 0) + (float) $row->amount * 10 ** ($digits($currency) - $digits($row->currency));
            $orders[$day] = ($orders[$day] ?? 0) + (int) $row->orders;
        }

        $result = ['dates' => [], 'revenue' => [], 'orders' => []];

        for ($date = $from->copy(); $date->lte(today()); $date->addDay()) {
            $key = $date->toDateString();
            $result['dates'][] = $date->copy();
            $result['revenue'][] = (int) round($revenue[$key] ?? 0);
            $result['orders'][] = $orders[$key] ?? 0;
        }

        return $result;
    }

    /**
     * Najpredávanejšie produkty podľa počtu kusov za posledných N dní.
     *
     * @return list<array{name: string, product_id: int|null, quantity: int}>
     */
    public function topProducts(int $days, int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->where('orders.created_at', '>=', today()->subDays($days - 1))
            ->selectRaw('MAX(order_items.name) as name, order_items.product_id, SUM(order_items.quantity) as quantity')
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->orderByDesc('quantity')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => ['name' => (string) $row->name, 'product_id' => (int) $row->product_id, 'quantity' => (int) $row->quantity])
            ->all();
    }
}
