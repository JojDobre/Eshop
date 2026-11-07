<?php

namespace Modules\Catalog\Listeners;

use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Catalog\Events\StockLow;
use Modules\Catalog\Mail\LowStockNotification;
use Throwable;

/**
 * Pošle obchodníkovi upozornenie, že tovar dochádza. Chyba e-mailu nesmie zastaviť objednávku.
 */
class NotifyAboutLowStock
{
    public function handle(StockLow $event): void
    {
        try {
            $recipient = app(MailSettings::class)->notificationAddress(app(GeneralSettings::class));
            Mail::to($recipient)->send(new LowStockNotification($event->product));
        } catch (Throwable $e) {
            Log::error('Low stock notification failed.', ['product' => $event->product->id, 'exception' => $e]);
        }
    }
}
