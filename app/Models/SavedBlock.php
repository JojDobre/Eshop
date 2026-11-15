<?php

namespace App\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * Uložená sekcia: skupina blokov, ktorá sa vkladá blokom „Uložená sekcia“ na úvodnú stránku, stránky,
 * kategórie či články. Úprava sa prejaví všade, kde je vložená.
 *
 * @property int $id
 * @property string $name
 * @property list<array<string, mixed>>|null $blocks
 */
class SavedBlock extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['blocks'];

    /**
     * @return list<array<string, mixed>>
     */
    public function blockList(): array
    {
        $blocks = $this->blocks;

        return is_array($blocks) ? array_values(array_filter($blocks, 'is_array')) : [];
    }
}
