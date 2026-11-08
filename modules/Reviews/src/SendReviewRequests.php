<?php

namespace Modules\Reviews;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Throwable;

/**
 * Pošle žiadosti o hodnotenie, ktorým nastal čas (webový cron, po dávkach).
 */
class SendReviewRequests
{
    public function __construct(private readonly Reviews $reviews, private readonly ReviewSettings $settings) {}

    public function handle(int $limit = 50): int
    {
        if (! ReviewSettings::isEnabled() || ! $this->settings->request_enabled) {
            return 0;
        }

        $sent = 0;
        $orders = Order::query()->whereNull('review_requested_at')->whereNotNull('review_request_due_at')
            ->where('review_request_due_at', '<=', now())->where('status', OrderStatus::Completed)
            ->orderBy('review_request_due_at')->limit($limit)->get();

        foreach ($orders as $order) {
            // Označí sa vopred, aby sa pri chybe e-mail neposielal dookola.
            $order->forceFill(['review_requested_at' => now()])->save();

            try {
                Mail::to($order->email, $order->billing_name)->send(new TemplateMail('reviews.request', [
                    'customer_name' => $order->billing_name,
                    'order_number' => $order->number,
                    'review_url' => $this->reviews->orderUrl($order),
                ], $order->locale, ['review_url']));
                $sent++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }
}
