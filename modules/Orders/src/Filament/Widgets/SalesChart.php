<?php

namespace Modules\Orders\Filament\Widgets;

use App\Core\Support\CurrencyContext;
use Brick\Money\Currency;
use Filament\Widgets\ChartWidget;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Reports\SalesReport;

/**
 * Graf tržieb (stĺpce) a počtu objednávok (čiara) za 7, 30 alebo 90 dní.
 */
class SalesChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '280px';

    public ?string $filter = '30';

    public static function canView(): bool
    {
        return OrderResource::canAccess();
    }

    public function getHeading(): string
    {
        return __('orders::admin.dashboard.sales');
    }

    protected function getFilters(): ?array
    {
        return collect([7, 30, 90])->mapWithKeys(fn (int $days) => [(string) $days => __('orders::admin.dashboard.last_days', ['days' => $days])])->all();
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $currency = app(CurrencyContext::class)->current();
        $days = in_array((int) $this->filter, [7, 30, 90], true) ? (int) $this->filter : 30;
        $data = app(SalesReport::class)->daily($days, $currency);
        $divisor = 10 ** Currency::of($currency)->getDefaultFractionDigits();

        return [
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => __('orders::admin.dashboard.revenue', ['currency' => $currency]),
                    'data' => array_map(fn (int $amount) => round($amount / $divisor, 2), $data['revenue']),
                    'yAxisID' => 'y',
                    'borderRadius' => 6,
                    'maxBarThickness' => 28,
                ],
                [
                    'type' => 'line',
                    'label' => __('orders::admin.dashboard.orders'),
                    'data' => $data['orders'],
                    'yAxisID' => 'y1',
                    'tension' => 0,
                    'pointRadius' => 2,
                    'borderWidth' => 2,
                    'borderColor' => '#64748b',
                    'backgroundColor' => '#64748b',
                ],
            ],
            'labels' => array_map(fn ($date) => $date->format('j. n.'), $data['dates']),
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']],
            'scales' => [
                'y' => ['beginAtZero' => true, 'grid' => ['display' => true]],
                'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['display' => false], 'ticks' => ['precision' => 0]],
                'x' => ['grid' => ['display' => false]],
            ],
        ];
    }
}
