<?php

namespace Modules\Orders\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Mail\PaymentReminderMail;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Orders\UnpaidOrderSettings;
use Modules\Payments\PaymentType;
use Throwable;

/**
 * Nezaplatené objednávky s platbou prevodom: po nastavenom počte dní pošle zákazníkovi pripomienku
 * a po ďalšom termíne objednávku zruší (tovar sa vráti na sklad, zákazník dostane e-mail o zrušení).
 * Spúšťa sa každú hodinu cez cron alebo webový cron.
 */
class HandleUnpaidOrders
{
    public function __construct(private readonly ChangeOrderStatus $status, private readonly AddOrderNote $notes) {}

    /**
     * @return array{reminded: int, cancelled: int}
     */
    public function handle(): array
    {
        $settings = UnpaidOrderSettings::current();
        $result = ['reminded' => 0, 'cancelled' => 0];

        if ($settings === null) {
            return $result;
        }

        if ($settings->cancel_after_days > 0) {
            foreach ($this->unpaid()->where('created_at', '<=', now()->subDays($settings->cancel_after_days))->get() as $order) {
                try {
                    $this->status->handle($order, OrderStatus::Cancelled);
                    $this->notes->handle($order, __('orders::admin.unpaid.cancelled_note', ['days' => $settings->cancel_after_days]));
                    $result['cancelled']++;
                } catch (Throwable $e) {
                    Log::error('Unpaid order cancellation failed.', ['order' => $order->number, 'exception' => $e]);
                }
            }
        }

        if ($settings->reminder_after_days > 0 && ($settings->cancel_after_days === 0 || $settings->reminder_after_days < $settings->cancel_after_days)) {
            $orders = $this->unpaid()->whereNull('payment_reminded_at')
                ->where('created_at', '<=', now()->subDays($settings->reminder_after_days))->get();

            foreach ($orders as $order) {
                $cancelDate = $settings->cancel_after_days > 0
                    ? $order->created_at->copy()->addDays($settings->cancel_after_days)->locale($order->locale ?: app()->getLocale())->isoFormat('D. M. YYYY')
                    : null;

                try {
                    Mail::to($order->email, $order->billing_name)->locale($order->locale)->send(new PaymentReminderMail($order, $cancelDate));
                    $order->forceFill(['payment_reminded_at' => now()])->saveQuietly();
                    $this->notes->handle($order, __('orders::admin.unpaid.reminded_note'));
                    $result['reminded']++;
                } catch (Throwable $e) {
                    Log::error('Payment reminder failed.', ['order' => $order->number, 'exception' => $e]);
                }
            }
        }

        return $result;
    }

    /** Nové nezaplatené objednávky s platbou prevodom (spracovávané, odoslané a zrušené sa nechávajú tak). */
    private function unpaid(): Builder
    {
        return Order::query()
            ->where('payment_type', PaymentType::BankTransfer->value)
            ->where('payment_status', PaymentStatus::Unpaid->value)
            ->where('status', OrderStatus::New->value);
    }
}
