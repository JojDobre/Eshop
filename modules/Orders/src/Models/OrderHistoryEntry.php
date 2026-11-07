<?php

namespace Modules\Orders\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;

/**
 * Záznam v histórii objednávky (vytvorenie, zmena stavu, platba, poznámka).
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $user_id
 * @property string $type
 * @property array<string, mixed>|null $data
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class OrderHistoryEntry extends Model
{
    public const CREATED = 'created';

    public const STATUS = 'status';

    public const PAYMENT = 'payment';

    public const NOTE = 'note';

    public const UPDATED_AT = null;

    protected $table = 'order_history';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Popis záznamu pre administráciu.
     */
    public function description(): string
    {
        $data = $this->data ?? [];

        return match ($this->type) {
            self::CREATED => __('orders::admin.history.created'),
            self::STATUS => __('orders::admin.history.status', [
                'from' => OrderStatus::tryFrom((string) ($data['from'] ?? ''))?->getLabel() ?? '—',
                'to' => OrderStatus::tryFrom((string) ($data['to'] ?? ''))?->getLabel() ?? '—',
            ]),
            self::PAYMENT => __('orders::admin.history.payment', [
                'status' => PaymentStatus::tryFrom((string) ($data['to'] ?? ''))?->getLabel() ?? '—',
            ]),
            self::NOTE => (string) ($data['note'] ?? ''),
            default => $this->type,
        };
    }
}
