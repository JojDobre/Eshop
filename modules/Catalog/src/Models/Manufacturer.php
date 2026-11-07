<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Výrobca produktu (údaje vyžadované nariadením GPSR).
 *
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $email
 * @property string|null $website
 * @property string|null $eu_responsible_person
 * @property string|null $safety_info
 */
class Manufacturer extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['safety_info'];

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
