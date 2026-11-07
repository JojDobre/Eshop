<?php

namespace Modules\Orders\Filament\Widgets;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use Brick\Money\Currency;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
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

        $counted = fn () => Order::query()->counted();
        $last30 = $counted()->where('created_at', '>=', now()->subDays(30));

        return [
            Stat::make($t('new_orders'), Order::query()->where('status', OrderStatus::New)->count())
                ->description($t('new_orders_description'))
                ->url(OrderResource::getUrl('index', ['filters' => ['status' => ['values' => [OrderStatus::New->value]]]])),
            Stat::make($t('revenue_today'), Price::format($this->revenue($counted()->whereDate('created_at', today()), $currency), $currency)),
            Stat::make($t('revenue_30_days'), Price::format($this->revenue(clone $last30, $currency), $currency))
                ->description(trans_choice('orders::admin.widget.orders_30_days', $count = (clone $last30)->count(), ['count' => $count])),
        ];
    }

    /**
     * Tržby v predvolenej mene: objednávky v iných menách sa prepočítajú kurzom z času nákupu.
     *
     * @param  Builder<Order>  $query
     */
    private function revenue(Builder $query, string $currency): int
    {
        $digits = fn (string $code) => Currency::of($code)->getDefaultFractionDigits();

        return (int) round($query->toBase()
            ->selectRaw('currency, SUM(total / exchange_rate) as amount')
            ->groupBy('currency')
            ->get()
            ->sum(fn ($row) => (float) $row->amount * 10 ** ($digits($currency) - $digits($row->currency))));
    }
}
