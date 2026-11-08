<?php

namespace Modules\Reviews;

use Illuminate\Support\Facades\URL;
use Modules\Catalog\Models\Product;
use Modules\Customers\Models\Customer;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Reviews\Models\Review;

/**
 * Pravidlá hodnotení: overenie nákupu, kto smie hodnotiť a odkaz na hodnotenie objednávky.
 */
class Reviews
{
    public function __construct(private readonly ReviewSettings $settings) {}

    /**
     * Objednávka zákazníka s týmto produktom (nezrušená) – dôkaz o kúpe.
     */
    public function purchaseOrder(Product $product, ?Customer $customer): ?Order
    {
        if ($customer === null) {
            return null;
        }

        return Order::query()
            ->where(fn ($query) => $query->where('customer_id', $customer->id)->orWhere('email', $customer->email))
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->latest('id')->first();
    }

    /**
     * Môže tento zákazník (alebo návštevník) produkt hodnotiť?
     */
    public function canReview(Product $product, ?Customer $customer): bool
    {
        if (! ReviewSettings::isEnabled()) {
            return false;
        }

        if ($this->settings->verified_only && $this->purchaseOrder($product, $customer) === null) {
            return false;
        }

        // Jeden zákazník = jedno hodnotenie produktu.
        return $customer === null || ! Review::query()->where('product_id', $product->id)->where('customer_id', $customer->id)->exists();
    }

    /**
     * Podpísaný odkaz na hodnotenie produktov z objednávky (platí 90 dní).
     */
    public function orderUrl(Order $order): string
    {
        return URL::temporarySignedRoute('reviews.order', now()->addDays(90), ['order' => $order->id]);
    }

    public function initialStatus(): string
    {
        return $this->settings->require_approval ? Review::PENDING : Review::APPROVED;
    }

    public function verificationInfo(): string
    {
        return filled($this->settings->verification_info)
            ? (string) $this->settings->verification_info
            : __($this->settings->verified_only ? 'reviews::shop.verification_verified_only' : 'reviews::shop.verification_default');
    }
}
