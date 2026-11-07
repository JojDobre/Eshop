<?php

namespace Modules\Returns\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Orders\Models\Order;
use Modules\Returns\ComplaintResolution;
use Modules\Returns\ReturnStatus;
use Modules\Returns\ReturnType;

/**
 * @property int $id
 * @property int $order_id
 * @property string $number
 * @property ReturnType $type
 * @property ReturnStatus $status
 * @property list<array{order_item_id: int, name: string, quantity: int}> $items
 * @property string|null $reason
 * @property ComplaintResolution|null $preferred_resolution
 * @property string|null $refund_account
 * @property Carbon $deadline_at
 * @property string|null $resolution
 * @property Carbon|null $resolved_at
 * @property-read Order $order
 */
class ReturnRequest extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => ReturnType::class,
            'status' => ReturnStatus::class,
            'preferred_resolution' => ComplaintResolution::class,
            'items' => 'array',
            'deadline_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isOverdue(): bool
    {
        return ! $this->status->isClosed() && $this->deadline_at->isPast();
    }

    /** Zostávajúce dni do lehoty (záporné = po lehote). */
    public function daysLeft(): int
    {
        return (int) floor(now()->startOfDay()->diffInDays($this->deadline_at->copy()->startOfDay(), false));
    }

    public function itemsSummary(): string
    {
        return collect($this->items)->map(fn (array $item) => $item['quantity'].' × '.$item['name'])->implode(', ');
    }
}
