<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * Vlastný stav dostupnosti zvolený pri produkte (napr. Skladom, Na objednávku do 5 dní, Nedostupné).
 *
 * @property int $id
 * @property string $name
 * @property string $color
 * @property bool $is_purchasable
 */
class AvailabilityStatus extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['is_purchasable' => 'boolean'];
    }

    public function safeColor(): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $this->color) ? $this->color : '#43a047';
    }
}
