<?php

namespace Modules\Orders\Actions;

use App\Settings\CatalogSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductFile;
use Modules\Orders\Mail\DownloadsMail;
use Modules\Orders\Models\DownloadLink;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Throwable;

/**
 * Vytvorí odkazy na stiahnutie digitálnych produktov z objednávky a pošle ich zákazníkovi.
 * Opakované volanie nevytvára duplicity (doplní iba chýbajúce odkazy).
 */
class IssueDownloads
{
    /**
     * @return int počet odkazov objednávky
     */
    public function handle(Order $order, bool $sendEmail = true): int
    {
        if (! CatalogFeatures::enabled('digital') || $order->status === OrderStatus::Cancelled) {
            return 0;
        }

        $settings = app(CatalogSettings::class);
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            $product = $item->product_id ? Product::query()->with('files')->find($item->product_id) : null;

            if ($product === null || ! $product->isDigital()) {
                continue;
            }

            foreach ($product->files as $file) {
                /** @var ProductFile $file */
                DownloadLink::query()->firstOrCreate(
                    ['order_item_id' => $item->id, 'product_file_id' => $file->id],
                    [
                        'order_id' => $order->id,
                        'file_name' => $file->name,
                        'token' => Str::random(48),
                        // Limit platí pre každý zakúpený kus.
                        'max_downloads' => $settings->digital_download_limit > 0 ? $settings->digital_download_limit * $item->quantity : null,
                        'expires_at' => $settings->digital_download_days > 0 ? now()->addDays($settings->digital_download_days) : null,
                    ],
                );
            }
        }

        $count = DownloadLink::query()->where('order_id', $order->id)->count();

        if ($count > 0 && $sendEmail) {
            try {
                Mail::to($order->email, $order->billing_name)->locale($order->locale)->send(new DownloadsMail($order));
            } catch (Throwable $e) {
                Log::error('Downloads e-mail failed.', ['order' => $order->number, 'exception' => $e]);
            }
        }

        return $count;
    }

    /**
     * Má objednávka digitálne produkty?
     */
    public static function hasDigitalItems(Order $order): bool
    {
        if (! CatalogFeatures::enabled('digital')) {
            return false;
        }

        $ids = $order->items->pluck('product_id')->filter();

        return $ids->isNotEmpty() && Product::query()->whereIn('id', $ids)->where('is_digital', true)->exists();
    }
}
