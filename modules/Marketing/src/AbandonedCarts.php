<?php

namespace Modules\Marketing;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Mail;
use Modules\Cart\Models\Cart;
use Throwable;

/**
 * Pripomienka opusteného košíka: jeden e-mail s odkazom na obnovenie košíka (webový cron).
 */
class AbandonedCarts
{
    public function __construct(private readonly MarketingSettings $settings) {}

    public static function enabled(): bool
    {
        return (bool) MarketingSettings::get('abandoned_enabled');
    }

    public function send(int $limit = 50): int
    {
        if (! self::enabled()) {
            return 0;
        }

        $sent = 0;
        $carts = Cart::query()->whereNotNull('email')->whereNull('reminded_at')
            ->where('updated_at', '<=', now()->subHours(max(1, $this->settings->abandoned_delay_hours)))
            // Staršie ako týždeň sa už nepripomínajú.
            ->where('updated_at', '>=', now()->subDays(7))
            ->whereHas('items')->with('items.product')->limit($limit)->get();

        foreach ($carts as $cart) {
            $cart->forceFill(['reminded_at' => now()])->saveQuietly();

            try {
                Mail::to($cart->email)->send(new TemplateMail('marketing.abandoned_cart', [
                    'cart_items' => $cart->items->map(fn ($item) => '- '.$item->product?->name.' × '.$item->quantity)->filter()->implode("\n"),
                    'cart_url' => route('marketing.cart.restore', $cart->token),
                ], $cart->locale, ['cart_items', 'cart_url']));
                $sent++;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }
}
