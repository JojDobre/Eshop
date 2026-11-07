<?php

namespace Modules\Pages\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $content
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $role
 * @property bool $show_in_footer
 * @property int $position
 * @property bool $is_visible
 */
class Page extends Model
{
    use HasContentTranslations, HasLocalizedSlug;

    public const ROLE_TERMS = 'terms';

    public const ROLE_PRIVACY = 'privacy';

    public const ROLE_SHIPPING = 'shipping';

    public const ROLE_CONTACT = 'contact';

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'slug', 'content', 'meta_title', 'meta_description'];

    protected $attributes = [
        'show_in_footer' => true,
        'position' => 0,
        'is_visible' => true,
    ];

    protected function casts(): array
    {
        return [
            'show_in_footer' => 'boolean',
            'position' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Page>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true)->orderBy('position')->orderBy('id');
    }

    public function url(): string
    {
        return route('pages.show', $this->slug);
    }
}
