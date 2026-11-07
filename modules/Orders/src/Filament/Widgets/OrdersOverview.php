<?php

namespace Modules\Orders\Filament\Widgets;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;

class OrdersOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return OrderResource::canAccess();
    }

    protected function getStats(): array
    {
        $currency = app(CurrencyContext::class)->current();
        $t = fn (string $key, array $replace = []) => __('orders::admin.widget.'.$key, $replace);

        $counted = fn () => Order::query()->counted()->where('currency', $currency);
        $last30 = $counted()->where('created_at', '>=', now()->subDays(30));

        return [
            Stat::make($t('new_orders'), Order::query()->where('status', OrderStatus::New)->count())
                ->description($t('new_orders_description'))
                ->url(OrderResource::getUrl('index', ['filters' => ['status' => ['values' => [OrderStatus::New->value]]]])),
            Stat::make($t('revenue_today'), Price::format((int) $counted()->whereDate('created_at', today())->sum('total'), $currency)),
            Stat::make($t('revenue_30_days'), Price::format((int) (clone $last30)->sum('total'), $currency))
                ->description($t('orders_30_days', ['count' => (clone $last30)->count()])),
        ];
    }
}
