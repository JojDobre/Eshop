<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hodnota možnosti, napr. „M" alebo „červená".
 *
 * @property int $id
 * @property int $product_option_id
 * @property string $value
 * @property string|null $color
 * @property int $position
 * @property-read ProductOption $option
 */
class ProductOptionValue extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['value'];

    /**
     * @return BelongsTo<ProductOption, $this>
     */
    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }
}
