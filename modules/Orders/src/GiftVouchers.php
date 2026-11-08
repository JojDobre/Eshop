<?php

namespace Modules\Orders;

use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\Cart\CartSummary;
use Modules\Orders\Models\GiftVoucher;
use Modules\Orders\Models\GiftVoucherUsage;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Throwable;

/**
 * Darčekové poukážky: uplatnenie v košíku (kód v relácii), vydanie po zaplatení a vrátenie pri zrušení.
 */
class GiftVouchers
{
    public const SESSION_KEY = 'checkout.gift_voucher';

    public function __construct(private readonly GiftVoucherSettings $settings) {}

    public function enabled(): bool
    {
        return GiftVoucherSettings::isEnabled();
    }

    public function apply(string $code): GiftVoucher
    {
        session()->put(self::SESSION_KEY, GiftVoucher::normalize($code));

        return GiftVoucher::findByCode($code);
    }

    public function remove(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Uplatnená poukážka, ak sa dá použiť na košík (inak null).
     */
    public function forCart(CartSummary $summary): ?GiftVoucher
    {
        if (! $this->enabled()) {
            return null;
        }

        $voucher = GiftVoucher::findByCode(session(self::SESSION_KEY));

        return $voucher !== null && $voucher->rejectionReason($summary->currency) === null ? $voucher : null;
    }

    /**
     * Vydá poukážky za zaplatené položky „darčeková poukážka“ (každý kus jedna poukážka) a pošle kódy e-mailom.
     *
     * @return list<GiftVoucher>
     */
    public function issueForOrder(Order $order): array
    {
        $issued = [];
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if (! ($item->product?->is_gift_voucher ?? false)) {
                continue;
            }

            $existing = GiftVoucher::query()->where('order_item_id', $item->id)->count();

            for ($i = $existing; $i < $item->quantity; $i++) {
                $issued[] = $this->create($item->unit_price, $order->currency, $order, $item);
            }
        }

        if ($issued !== []) {
            $this->send($issued, $order->email, $order->billing_name, $order->locale);
        }

        return $issued;
    }

    public function create(int $amount, string $currency, ?Order $order = null, ?OrderItem $item = null, ?string $email = null, ?string $note = null): GiftVoucher
    {
        return GiftVoucher::query()->create([
            'code' => GiftVoucher::generateCode(),
            'initial_amount' => $amount,
            'balance' => $amount,
            'currency' => $currency,
            'expires_at' => $this->settings->validity_months > 0 ? now()->addMonths($this->settings->validity_months)->endOfDay() : null,
            'order_id' => $order?->id,
            'order_item_id' => $item?->id,
            'recipient_email' => $email ?? $order?->email,
            'note' => $note,
        ]);
    }

    /**
     * Pošle kódy poukážok e-mailom (šablóna „Darčeková poukážka“).
     *
     * @param  list<GiftVoucher>  $vouchers
     */
    public function send(array $vouchers, string $email, ?string $name, ?string $locale = null): void
    {
        $list = collect($vouchers)->map(fn (GiftVoucher $voucher) => '- **'.$voucher->formattedCode().'** – '.format_price($voucher->initial_amount, $voucher->currency)
            .($voucher->expires_at ? ' ('.__('orders::mail.voucher_valid_until', ['date' => $voucher->expires_at->format('j. n. Y')], $locale).')' : ''))->implode("\n");

        try {
            Mail::to($email, $name)->send(new TemplateMail('orders.gift_voucher', [
                'customer_name' => (string) $name,
                'voucher_codes' => $list,
                'shop_url' => url('/'),
            ], $locale, ['voucher_codes']));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Vráti na poukážku sumu čerpanú v zrušenej objednávke (iba raz).
     */
    public function refundForOrder(Order $order): void
    {
        if ($order->voucher_amount <= 0 || blank($order->voucher_code)) {
            return;
        }

        DB::transaction(function () use ($order): void {
            $voucher = GiftVoucher::query()->where('code', $order->voucher_code)->lockForUpdate()->first();

            if ($voucher === null) {
                return;
            }

            $net = (int) GiftVoucherUsage::query()->where('gift_voucher_id', $voucher->id)->where('order_id', $order->id)->sum('amount');

            if ($net <= 0) {
                return;
            }

            $voucher->increment('balance', $net);
            $voucher->usages()->create(['order_id' => $order->id, 'amount' => -$net]);
        });
    }
}
