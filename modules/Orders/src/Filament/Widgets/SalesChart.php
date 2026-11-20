<?php

namespace Modules\Orders\Filament\Widgets;

use App\Core\Support\CurrencyContext;
use App\Settings\BrandingSettings;
use Brick\Money\Currency;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Reports\SalesReport;

/**
 * Graf denných tržieb za 7, 30 alebo 90 dní; počet objednávok je v bublinke pri stĺpci.
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
        $color = rescue(fn () => app(BrandingSettings::class)->primaryColor(), BrandingSettings::DEFAULT_PRIMARY_COLOR, false);

        // Jeden rad: tržby ako plné stĺpce vo farbe obchodu; počet objednávok je v bublinke pri stĺpci.
        return [
            'datasets' => [
                [
                    'label' => __('orders::admin.dashboard.revenue', ['currency' => $currency]),
                    'data' => array_map(fn (int $amount) => round($amount / $divisor, 2), $data['revenue']),
                    'orders' => $data['orders'],
                    'backgroundColor' => $color,
                    'hoverBackgroundColor' => $color,
                    'borderWidth' => 0,
                    'borderRadius' => 6,
                    'maxBarThickness' => 32,
                    'minBarLength' => 0,
                ],
            ],
            'labels' => array_map(fn ($date) => $date->format('j. n.'), $data['dates']),
        ];
    }

    protected function getOptions(): RawJs
    {
        // Texty v jednoduchých úvodzovkách – nastavenia grafu sa vkladajú do atribútu HTML.
        $quote = fn (string $text) => "'".str_replace(['\\', "'", '"'], ['\\\\', "\\'", '&quot;'], $text)."'";
        $orders = $quote(__('orders::admin.dashboard.orders'));
        $currency = $quote(app(CurrencyContext::class)->current());

        return RawJs::make(<<<JS
            {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        displayColors: false,
                        callbacks: {
                            label: (context) => context.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + {$currency},
                            afterLabel: (context) => {$orders} + ': ' + (context.dataset.orders?.[context.dataIndex] ?? 0),
                        },
                    },
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(127,127,127,0.12)' }, border: { display: false }, ticks: { maxTicksLimit: 5 } },
                    x: { grid: { display: false }, border: { display: false }, ticks: { autoSkip: true, maxTicksLimit: 10, maxRotation: 0 } },
                },
            }
        JS);
    }
}
