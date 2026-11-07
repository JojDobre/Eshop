<?php

namespace Modules\Orders;

use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Events\OrderStatusChanged;
use Modules\Orders\Listeners\NotifyCustomerAboutStatus;
use Modules\Orders\Listeners\SendOrderEmails;
use Modules\Shipping\Models\ShippingMethod;

class OrdersServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'orders';
    }

    public function register(): void
    {
        $this->app->singleton(OrderMailAttachments::class);
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(OrderPlaced::class, SendOrderEmails::class);
        Event::listen(OrderStatusChanged::class, NotifyCustomerAboutStatus::class);

        $this->registerEmailTemplates();

        // Košík: koľko chýba do dopravy zadarmo (najnižšia hranica zo spôsobov doručenia).
        View::composer('theme::templates.cart', function (\Illuminate\View\View $view): void {
            $threshold = ShippingMethod::query()->active()->whereNotNull('free_from')->where('price', '>', 0)->min('free_from');
            $summary = $view->getData()['summary'] ?? null;
            $coupon = $summary ? app(Coupons::class)->forCart($summary) : null;

            $view->with([
                'freeShippingFrom' => $threshold === null ? null : (int) $threshold,
                'coupon' => $coupon,
                'couponDiscount' => $coupon && $summary ? $coupon->discountFor($summary->subtotal()) : 0,
                'couponsEnabled' => true,
            ]);
        });
    }

    private function registerEmailTemplates(): void
    {
        $templates = $this->app->make(EmailTemplates::class);
        $order = ['customer_name', 'order_number', 'order_total', 'order_items', 'payment_details', 'shipping_details', 'order_url', 'shop_name'];
        $sample = [
            'customer_name' => 'Jana Nováková', 'customer_email' => 'jana@example.com', 'customer_phone' => '+421 900 123 456',
            'order_number' => now()->format('Y').'00042', 'order_total' => '42,90 €', 'order_status' => OrderStatus::Shipped->getLabel(),
            'shipping_method' => 'Kuriér', 'payment_method' => 'Bankový prevod', 'customer_note' => '',
            'order_items' => "<x-mail::table>\n| Produkt | Ks | Cena |\n|:--|:-:|--:|\n| Ukážkový produkt | 2 | 38,00 € |\n| Kuriér | | 4,90 € |\n| **Spolu** | | **42,90 €** |\n</x-mail::table>",
            'payment_details' => '', 'shipping_details' => '', 'order_url' => url('/'), 'admin_url' => url('/admin'),
        ];

        $templates->register(new EmailTemplateDefinition('orders.confirmation', 'orders::mail_templates.confirmation', $order, sample: $sample));
        $templates->register(new EmailTemplateDefinition('orders.notification', 'orders::mail_templates.notification',
            ['order_number', 'customer_name', 'customer_email', 'customer_phone', 'order_total', 'order_items', 'shipping_method', 'payment_method', 'customer_note', 'admin_url'], sample: $sample));

        foreach ([OrderStatus::Processing, OrderStatus::ReadyForPickup, OrderStatus::Shipped, OrderStatus::Completed, OrderStatus::Cancelled] as $status) {
            $templates->register(new EmailTemplateDefinition(
                'orders.status.'.$status->value,
                'orders::mail_templates.status_'.$status->value,
                ['customer_name', 'order_number', 'order_status', 'shipping_details', 'order_url', 'shop_name'],
                canBeDisabled: true,
                sample: $sample,
            ));
        }
    }
}
