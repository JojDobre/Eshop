<?php

namespace Modules\Orders;

use App\Core\Features\FeatureToggle;
use App\Core\Features\FeatureToggles;
use App\Core\Installer\Installation;
use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Support\CurrencyContext;
use App\Settings\GeneralSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\Attachment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Modules\Orders\Actions\TrackShipments;
use Modules\Orders\Events\OrderPaid;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Events\OrderStatusChanged;
use Modules\Orders\Listeners\IssueDownloadsForOrder;
use Modules\Orders\Listeners\IssueGiftVouchers;
use Modules\Orders\Listeners\NotifyCustomerAboutStatus;
use Modules\Orders\Listeners\RefundGiftVoucher;
use Modules\Orders\Listeners\SendOrderEmails;
use Modules\Orders\Models\Order;
use Modules\Orders\Payments\PaymentGateways;
use Modules\Payments\PaymentType;
use Modules\Payments\Qr\PaymentQr;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Storefront\Pages\LegalPages;

class OrdersServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'orders';
    }

    public function register(): void
    {
        $this->app->singleton(PaymentGateways::class);
        $this->app->singleton(OrderMailAttachments::class);
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(OrderPlaced::class, SendOrderEmails::class);
        Event::listen(OrderStatusChanged::class, NotifyCustomerAboutStatus::class);
        Event::listen([OrderPlaced::class, OrderPaid::class], IssueDownloadsForOrder::class);
        Event::listen(OrderPaid::class, IssueGiftVouchers::class);
        Event::listen(OrderStatusChanged::class, RefundGiftVoucher::class);

        // Darčekové poukážky: prepínač vo Funkciách obchodu.
        $toggles = $this->app->make(FeatureToggles::class);
        $toggles->group('orders', fn () => __('orders::admin.features.group'));
        $toggles->register(new FeatureToggle(
            key: 'orders.gift_vouchers',
            group: 'orders',
            label: fn () => __('orders::admin.features.gift_vouchers'),
            description: fn () => __('orders::admin.features.gift_vouchers_help'),
            isEnabled: fn () => GiftVoucherSettings::isEnabled(),
            set: function (bool $enabled): void {
                $settings = app(GiftVoucherSettings::class);
                $settings->enabled = $enabled;
                $settings->save();
            },
            settingsUrl: fn () => url('/admin/settings/gift-vouchers'),
            sizes: ['small' => false, 'medium' => true, 'large' => true],
        ));

        // Kontrola stavu zásielok u kuriérov (webový cron).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(TrackShipments::class)->handle())
                ->name('orders:track-shipments')
                ->everyThirtyMinutes()
                ->withoutOverlapping(20)
                ->when(fn () => app(Installation::class)->isInstalled());
        });

        $this->registerEmailTemplates();

        // Obchodné podmienky v prílohe potvrdenia (zmluvné podmienky na trvanlivom nosiči).
        $this->app->make(OrderMailAttachments::class)->register(OrderMailAttachments::CONFIRMATION, function (Order $order): ?Attachment {
            $document = app(LegalPages::class)->current(LegalPages::TERMS);

            if ($document === null || blank(strip_tags($document->html))) {
                return null;
            }

            $shopName = app(GeneralSettings::class)->shop_name;

            return Attachment::fromData(
                fn () => Pdf::loadView('orders::pdf.legal-document', ['document' => $document, 'shopName' => $shopName])->setPaper('a4')->output(),
                Str::slug($document->title).'-v'.$document->version.'.pdf',
            )->withMime('application/pdf');
        });

        // Košík: koľko chýba do dopravy zadarmo (najnižšia hranica zo spôsobov doručenia).
        // QR kód na platbu prevodom na stránke objednávky.
        View::composer('theme::templates.order', function (\Illuminate\View\View $view): void {
            $order = $view->getData()['order'] ?? null;
            $qr = null;

            if ($order instanceof Order && $order->payment_type === PaymentType::BankTransfer && ! $order->isPaid() && ! $order->isCancelled()) {
                $data = $order->paymentQrData();
                $qr = $data ? app(PaymentQr::class)->dataUri($data) : null;
            }

            $view->with('paymentQr', $qr);
            $view->with('orderFields', $order instanceof Order ? CheckoutFields::display($order->id) : []);
        });

        View::composer('theme::templates.cart', function (\Illuminate\View\View $view): void {
            $summary = $view->getData()['summary'] ?? null;
            $currency = $summary->currency ?? app(CurrencyContext::class)->current();
            // Najnižšia hranica dopravy zadarmo v mene košíka.
            $threshold = ShippingMethod::query()->active()->whereNotNull('free_from')->where('price', '>', 0)->get()
                ->map(fn (ShippingMethod $method) => $method->freeFromIn($currency))->filter()->min();
            $coupon = $summary ? app(Coupons::class)->forCart($summary) : null;
            $vouchers = app(GiftVouchers::class);

            $view->with([
                'freeShippingFrom' => $threshold === null ? null : (int) $threshold,
                'coupon' => $coupon,
                'couponDiscount' => $coupon && $summary ? $coupon->discountFor($summary->subtotal(), $summary->currency) : 0,
                'couponsEnabled' => true,
                'vouchersEnabled' => $vouchers->enabled(),
                'giftVoucher' => $summary ? $vouchers->forCart($summary) : null,
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
            'payment_details' => '', 'shipping_details' => '', 'tracking' => '[Sledovať zásielku Z 123 4567 890](https://tracking.packeta.com/)', 'order_url' => url('/'), 'admin_url' => url('/admin'),
        ];

        $templates->register(new EmailTemplateDefinition('orders.confirmation', 'orders::mail_templates.confirmation', $order, sample: $sample));
        $templates->register(new EmailTemplateDefinition('orders.notification', 'orders::mail_templates.notification',
            ['order_number', 'customer_name', 'customer_email', 'customer_phone', 'order_total', 'order_items', 'shipping_method', 'payment_method', 'customer_note', 'admin_url'], sample: $sample));

        $templates->register(new EmailTemplateDefinition('orders.gift_voucher', 'orders::mail_templates.gift_voucher',
            ['customer_name', 'voucher_codes', 'shop_url', 'shop_name'],
            sample: [...$sample, 'voucher_codes' => '- **GV7K-2M9Q-X4HT** – 50,00 €', 'shop_url' => url('/')]));
        $templates->register(new EmailTemplateDefinition('orders.downloads', 'orders::mail_templates.downloads',
            ['customer_name', 'order_number', 'download_links', 'order_url', 'shop_name'],
            sample: [...$sample, 'download_links' => '- [ebook.pdf]('.url('/').')']));

        foreach ([OrderStatus::Processing, OrderStatus::ReadyForPickup, OrderStatus::Shipped, OrderStatus::Completed, OrderStatus::Cancelled] as $status) {
            $templates->register(new EmailTemplateDefinition(
                'orders.status.'.$status->value,
                'orders::mail_templates.status_'.$status->value,
                ['customer_name', 'order_number', 'order_status', 'shipping_details', 'tracking', 'order_url', 'shop_name'],
                canBeDisabled: true,
                sample: $sample,
            ));
        }
    }
}
