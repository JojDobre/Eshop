<?php

namespace Modules\Orders\Models;

use App\Core\Content\LocalizedUrls;
use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Payments\PaymentType;
use Modules\Payments\Qr\PaymentQrData;
use Modules\Shipping\ShippingType;
use Modules\Tax\TaxCalculator;

/**
 * @property int $id
 * @property string $number
 * @property string $token
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property string $locale
 * @property string $currency
 * @property string $email
 * @property string|null $phone
 * @property string $billing_name
 * @property string|null $billing_company
 * @property string|null $billing_company_id
 * @property string|null $billing_tax_id
 * @property string|null $billing_vat_id
 * @property string $billing_street
 * @property string $billing_city
 * @property string $billing_zip
 * @property string $billing_country
 * @property string|null $shipping_name
 * @property string|null $shipping_street
 * @property string|null $shipping_city
 * @property string|null $shipping_zip
 * @property string|null $shipping_country
 * @property int|null $shipping_method_id
 * @property string $shipping_method_name
 * @property ShippingType $shipping_type
 * @property string|null $shipping_details
 * @property int $shipping_price
 * @property int|null $payment_method_id
 * @property string $payment_method_name
 * @property PaymentType $payment_type
 * @property string|null $payment_instructions
 * @property int $payment_fee
 * @property string|null $coupon_code
 * @property int $discount
 * @property int $subtotal
 * @property int $total
 * @property string|null $customer_note
 * @property Carbon|null $terms_accepted_at
 * @property Carbon|null $paid_at
 * @property Carbon $created_at
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, OrderHistoryEntry> $history
 */
class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'shipping_type' => ShippingType::class,
            'payment_type' => PaymentType::class,
            'vat_payer' => 'boolean',
            'legal_consents' => 'array',
            'shipping_price' => 'integer',
            'shipping_tax_rate' => 'float',
            'payment_tax_rate' => 'float',
            'payment_fee' => 'integer',
            'discount' => 'integer',
            'subtotal' => 'integer',
            'total' => 'integer',
            'terms_accepted_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return HasMany<OrderHistoryEntry, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(OrderHistoryEntry::class)->latest('id');
    }

    /**
     * Objednávky, ktoré sa započítavajú do tržieb (bez stornovaných).
     *
     * @param  Builder<Order>  $query
     */
    public function scopeCounted(Builder $query): void
    {
        $query->where('status', '!=', OrderStatus::Cancelled);
    }

    /**
     * Rozpis DPH podľa sadzieb (tovar, doprava, platba).
     *
     * @return array<string, array{rate: float, net: int, vat: int, gross: int}>
     */
    public function vatBreakdown(): array
    {
        $lines = $this->items->map(fn (OrderItem $item) => ['gross' => $item->total, 'rate' => (float) $item->tax_rate])->all();

        // Zľava sa rozdelí na sadzby DPH pomerne podľa hodnoty tovaru (doprava a platba sa nezľavujú).
        if ($this->discount > 0) {
            $itemsTotal = max(1, array_sum(array_column($lines, 'gross')));
            $remaining = $this->discount;

            foreach ($lines as $index => $line) {
                $share = $index === array_key_last($lines) ? $remaining : (int) round($this->discount * $line['gross'] / $itemsTotal);
                $lines[$index]['gross'] -= $share;
                $remaining -= $share;
            }
        }

        if ($this->shipping_price > 0) {
            $lines[] = ['gross' => $this->shipping_price, 'rate' => (float) $this->shipping_tax_rate];
        }

        if ($this->payment_fee > 0) {
            $lines[] = ['gross' => $this->payment_fee, 'rate' => (float) $this->payment_tax_rate];
        }

        return TaxCalculator::breakdown($lines);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Paid;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    /**
     * Variabilný symbol pre bankový prevod (číslice z čísla objednávky).
     */
    /**
     * Údaje pre QR kód na platbu prevodom (null, ak obchod nemá zadaný účet).
     */
    public function paymentQrData(): ?PaymentQrData
    {
        $company = app(CompanySettings::class);

        if (blank($company->iban)) {
            return null;
        }

        return new PaymentQrData(
            iban: $company->iban,
            bic: $company->swift,
            amount: $this->total,
            currency: $this->currency,
            variableSymbol: $this->variableSymbol(),
            beneficiaryName: $company->company_name ?: app(GeneralSettings::class)->shop_name,
            message: __('orders::mail.qr_message', ['number' => $this->number], $this->locale),
        );
    }

    public function variableSymbol(): string
    {
        return substr((string) preg_replace('/\D/', '', $this->number), -10);
    }

    public function hasShippingAddress(): bool
    {
        return filled($this->shipping_street);
    }

    public function publicUrl(): string
    {
        // Odkaz v jazyku objednávky (aj keď ho vytvára administrácia alebo e-mail).
        return app(LocalizedUrls::class)->in($this->locale, fn () => route('orders.show', $this->token));
    }

    public function billingCompanyLine(): ?string
    {
        return $this->billing_company ?: null;
    }
}
