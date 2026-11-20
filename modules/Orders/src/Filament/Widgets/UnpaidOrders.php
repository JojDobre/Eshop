<?php

namespace Modules\Orders\Filament\Widgets;

use App\Core\Support\Price;
use Filament\Widgets\Widget;
use Modules\Orders\Filament\Pages\ManageUnpaidOrderSettings;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Payments\PaymentType;

/**
 * Nové objednávky s platbou prevodom, ktoré ešte nie sú zaplatené – najstaršie hore.
 */
class UnpaidOrders extends Widget
{
    protected static ?int $sort = 4;

    protected string $view = 'orders::filament.widgets.unpaid-orders';

    public static function canView(): bool
    {
        return OrderResource::canAccess();
    }

    /**
     * @return array{rows: list<array{number: string, customer: string, total: string, days: int, reminded: bool, url: string}>, count: int}
     */
    public function data(): array
    {
        $query = Order::query()
            ->where('payment_type', PaymentType::BankTransfer->value)
            ->where('payment_status', PaymentStatus::Unpaid->value)
            ->where('status', OrderStatus::New->value);

        return [
            'count' => (clone $query)->count(),
            'rows' => $query->oldest()->limit(6)->get()->map(fn (Order $order) => [
                'number' => $order->number,
                'customer' => $order->billing_name,
                'total' => Price::format($order->amountDue(), $order->currency),
                'days' => (int) $order->created_at->diffInDays(now()),
                'reminded' => $order->payment_reminded_at !== null,
                'url' => OrderResource::getUrl('view', ['record' => $order]),
            ])->all(),
        ];
    }

    public function settingsUrl(): string
    {
        return ManageUnpaidOrderSettings::getUrl();
    }
}
