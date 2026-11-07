<?php

namespace Modules\Orders\Listeners;

use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Mail\NewOrderNotification;
use Modules\Orders\Mail\OrderConfirmation;
use Throwable;

/**
 * Po objednávke pošle potvrdenie zákazníkovi a upozornenie obchodníkovi.
 *
 * Zlyhanie e-mailu (napr. nesprávne nastavený SMTP server) nesmie zrušiť objednávku:
 * chyba sa zapíše do logu a objednávka zostane platná.
 */
class SendOrderEmails
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        try {
            Mail::to($order->email, $order->billing_name)->locale($order->locale)->send(new OrderConfirmation($order));
        } catch (Throwable $e) {
            Log::error('Order confirmation e-mail failed.', ['order' => $order->number, 'exception' => $e]);
        }

        try {
            $recipient = app(MailSettings::class)->notificationAddress(app(GeneralSettings::class));
            Mail::to($recipient)->send(new NewOrderNotification($order));
        } catch (Throwable $e) {
            Log::error('New order notification e-mail failed.', ['order' => $order->number, 'exception' => $e]);
        }
    }
}
