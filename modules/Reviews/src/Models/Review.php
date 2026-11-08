<?php

namespace Modules\Reviews\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;
use Modules\Orders\Models\Order;
use Modules\Reviews\ReviewStats;

/**
 * Hodnotenie produktu (1–5 hviezdičiek). Overený nákup = má objednávku.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $customer_id
 * @property int|null $order_id
 * @property string $author_name
 * @property string|null $email
 * @property int $rating
 * @property string|null $title
 * @property string|null $body
 * @property string|null $pros
 * @property string|null $cons
 * @property string $status pending, approved, rejected
 * @property string|null $reply
 */
class Review extends Model
{
    public const PENDING = 'pending';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    protected $table = 'product_reviews';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'replied_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        // Súhrn produktu sa prepočíta pri každej zmene schválených hodnotení.
        static::saved(fn (Review $review) => ReviewStats::refresh($review->product_id));
        static::deleted(fn (Review $review) => ReviewStats::refresh($review->product_id));
    }

    /**
     * @param  Builder<Review>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::APPROVED);
    }

    public function isVerified(): bool
    {
        return $this->order_id !== null;
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
