<?php

namespace App\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * Upravený text e-mailu (predmet a telo v Markdowne s premennými, napr. {order_number}).
 *
 * @property int $id
 * @property string $key
 * @property string|null $subject
 * @property string|null $body
 * @property bool $is_active
 */
class EmailTemplate extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['subject', 'body'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
}
