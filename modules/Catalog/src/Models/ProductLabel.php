<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Štítok produktu zobrazený na karte aj na detaile (napr. Vlajková loď, Bio).
 *
 * @property int $id
 * @property string $name
 * @property string $color
 * @property string $text_color
 */
class ProductLabel extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name'];

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_label_product');
    }

    /** Bezpečný štýl (farby sú overené pri uložení, tu sa ešte raz skontrolujú). */
    public function style(): string
    {
        $hex = fn (string $value, string $default) => preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? $value : $default;

        return 'background-color: '.$hex($this->color, '#e8772a').'; color: '.$hex($this->text_color, '#ffffff');
    }
}
