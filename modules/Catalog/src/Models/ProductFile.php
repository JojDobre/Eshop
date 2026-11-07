<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Súbor digitálneho produktu (na súkromnom disku „local“, nie je dostupný priamo z webu).
 *
 * @property int $id
 * @property int $product_id
 * @property string $name Názov súboru pre zákazníka.
 * @property string $path
 * @property int $position
 */
class ProductFile extends Model
{
    public const DISK = 'local';

    public const DIRECTORY = 'digital';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['position' => 'integer'];
    }

    protected static function booted(): void
    {
        // Zmazaním záznamu sa zmaže aj súbor.
        static::deleted(fn (ProductFile $file) => Storage::disk(self::DISK)->delete($file->path));
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function exists(): bool
    {
        return Storage::disk(self::DISK)->exists($this->path);
    }
}
