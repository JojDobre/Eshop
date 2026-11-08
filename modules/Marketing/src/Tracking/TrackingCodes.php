<?php

namespace Modules\Marketing\Tracking;

use App\Core\Support\Price;
use Modules\Marketing\MarketingSettings;
use Modules\Orders\Models\Order;

/**
 * Meracie kódy Google Analytics 4, Google Ads a Meta Pixel vrátane udalosti nákupu na stránke
 * dokončenej objednávky. Vkladajú sa iba so súhlasom s cookies (CookieConsent::addCode).
 */
class TrackingCodes
{
    /** Objednávka práve dokončená v tejto požiadavke (konverzia). */
    private ?Order $purchase = null;

    public const ID_PATTERNS = [
        'ga4_id' => '/^G-[A-Z0-9]{4,20}$/',
        'google_ads_id' => '/^AW-\d{5,15}$/',
        'google_ads_label' => '/^[A-Za-z0-9_\-]{4,40}$/',
        'meta_pixel_id' => '/^\d{8,20}$/',
    ];

    public function setPurchase(Order $order): void
    {
        $this->purchase = $order;
    }

    private static function id(string $key): ?string
    {
        $value = trim((string) MarketingSettings::get($key, ''));

        return $value !== '' && preg_match(self::ID_PATTERNS[$key], $value) ? $value : null;
    }

    public function analytics(): ?string
    {
        $id = self::id('ga4_id');

        if ($id === null) {
            return null;
        }

        $script = $this->gtagLoader($id)."gtag('config', ".json_encode($id).');';

        if ($this->purchase !== null) {
            $script .= "gtag('event', 'purchase', ".json_encode($this->ga4Purchase($this->purchase), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG).');';
        }

        return $script.'</script>';
    }

    public function marketing(): ?string
    {
        $codes = [];

        if ($ads = self::id('google_ads_id')) {
            $script = $this->gtagLoader($ads)."gtag('config', ".json_encode($ads).');';
            $label = self::id('google_ads_label');

            if ($this->purchase !== null && $label !== null) {
                $script .= "gtag('event', 'conversion', ".json_encode([
                    'send_to' => $ads.'/'.$label,
                    'value' => $this->amount($this->purchase),
                    'currency' => $this->purchase->currency,
                    'transaction_id' => $this->purchase->number,
                ]).');';
            }

            $codes[] = $script.'</script>';
        }

        if ($pixel = self::id('meta_pixel_id')) {
            $purchase = $this->purchase !== null
                ? "fbq('track', 'Purchase', ".json_encode(['value' => $this->amount($this->purchase), 'currency' => $this->purchase->currency, 'content_type' => 'product',
                    'contents' => $this->purchase->items->map(fn ($item) => ['id' => (string) $item->product_id, 'quantity' => $item->quantity])->values()->all()]).');'
                : '';

            $codes[] = "<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');"
                ."fbq('init', ".json_encode($pixel).");fbq('track', 'PageView');".$purchase.'</script>';
        }

        return $codes === [] ? null : implode("\n", $codes);
    }

    private function gtagLoader(string $id): string
    {
        return '<script async src="https://www.googletagmanager.com/gtag/js?id='.rawurlencode($id).'"></script>'
            ."<script>window.dataLayer = window.dataLayer || []; function gtag(){dataLayer.push(arguments);} gtag('js', new Date());";
    }

    /**
     * @return array<string, mixed>
     */
    private function ga4Purchase(Order $order): array
    {
        return [
            'transaction_id' => $order->number,
            'value' => $this->amount($order),
            'currency' => $order->currency,
            'shipping' => (float) Price::toDecimal((int) $order->shipping_price, $order->currency),
            'items' => $order->items->map(fn ($item) => [
                'item_id' => (string) ($item->sku ?: $item->product_id),
                'item_name' => $item->name.($item->variant_name ? ' '.$item->variant_name : ''),
                'price' => (float) Price::toDecimal((int) $item->unit_price, $order->currency),
                'quantity' => $item->quantity,
            ])->values()->all(),
        ];
    }

    private function amount(Order $order): float
    {
        return (float) Price::toDecimal((int) $order->total, $order->currency);
    }
}
