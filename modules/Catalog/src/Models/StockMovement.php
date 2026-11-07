<?php

namespace Modules\Catalog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Zmena stavu skladu.
 *
 * @property int $id
 * @property int $product_id
 * @property int $quantity Zmena (+ príjem, − výdaj).
 * @property int $stock_after Stav po zmene.
 * @property string $type
 * @property string|null $reference
 * @property string|null $note
 * @property int|null $user_id
 * @property Carbon $created_at
 * @property-read User|null $user
 */
class StockMovement extends Model
{
    public const ORDER = 'order';

    public const CANCEL = 'cancel';

    public const RESTORE = 'restore';

    public const MANUAL = 'manual';

    public const EDIT = 'edit';

    public const RECEIPT = 'receipt';

    public const TRANSFER = 'transfer';

    public const WRITE_OFF = 'write_off';

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'stock_after' => 'integer'];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
