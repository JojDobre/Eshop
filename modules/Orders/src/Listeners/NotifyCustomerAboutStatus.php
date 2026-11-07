<?php

namespace Modules\Orders\Listeners;

use App\Core\Mail\EmailTemplates;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Events\OrderStatusChanged;
use Modules\Orders\Mail\OrderStatusMail;
use Throwable;

/**
 * Pošle zákazníkovi e-mail o zmene stavu, ak to obchodník pri zmene nevypol a e-mail pre stav je zapnutý.
 */
class NotifyCustomerAboutStatus
{
    public function handle(OrderStatusChanged $event): void
    {
        $key = OrderStatusMail::templateKey($event->order);
        $templates = app(EmailTemplates::class);

        if (! $event->notifyCustomer || $templates->definition($key) === null || ! $templates->isEnabled($key)) {
            return;
        }

        try {
            Mail::to($event->order->email, $event->order->billing_name)->locale($event->order->locale)->send(new OrderStatusMail($event->order));
        } catch (Throwable $e) {
            Log::error('Order status e-mail failed.', ['order' => $event->order->number, 'exception' => $e]);
        }
    }
}
