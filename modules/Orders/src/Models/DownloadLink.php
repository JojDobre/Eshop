<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Catalog\Models\ProductFile;
use Modules\Orders\OrderStatus;

/**
 * Odkaz na stiahnutie súboru z objednávky (náhodný kľúč, obmedzený počet a platnosť).
 *
 * @property int $id
 * @property int $order_id
 * @property int $order_item_id
 * @property int $product_file_id
 * @property string $file_name
 * @property string $token
 * @property int $downloads
 * @property int|null $max_downloads
 * @property Carbon|null $expires_at
 * @property Carbon|null $last_downloaded_at
 * @property-read Order $order
 * @property-read ProductFile|null $file
 */
class DownloadLink extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['downloads' => 'integer', 'max_downloads' => 'integer', 'expires_at' => 'datetime', 'last_downloaded_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<ProductFile, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(ProductFile::class, 'product_file_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isExhausted(): bool
    {
        return $this->max_downloads !== null && $this->downloads >= $this->max_downloads;
    }

    /** Dá sa súbor stiahnuť? (nezrušená objednávka, platnosť, počet stiahnutí) */
    public function isUsable(): bool
    {
        return $this->order->status !== OrderStatus::Cancelled && ! $this->isExpired() && ! $this->isExhausted();
    }

    public function remainingDownloads(): ?int
    {
        return $this->max_downloads === null ? null : max(0, $this->max_downloads - $this->downloads);
    }

    public function url(): string
    {
        return route('downloads.show', $this->token);
    }
}
