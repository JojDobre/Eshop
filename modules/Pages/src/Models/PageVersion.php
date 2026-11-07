<?php

namespace Modules\Pages\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $page_id
 * @property int $version
 * @property string $name
 * @property string|null $content
 * @property Carbon $created_at
 */
class PageVersion extends Model
{
    use HasContentTranslations;

    public const UPDATED_AT = null;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'content'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Page versions are immutable.'));
    }

    /**
     * @return BelongsTo<Page, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function url(): string
    {
        return route('pages.version', [$this->page->slug, $this->version]);
    }
}
