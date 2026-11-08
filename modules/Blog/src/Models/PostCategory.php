<?php

namespace Modules\Blog\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
use App\Core\Content\LocalizedUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 */
class PostCategory extends Model
{
    use HasContentTranslations, HasLocalizedSlug;

    protected $table = 'blog_categories';

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'slug', 'description'];

    /**
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'blog_category_id');
    }

    public function url(): string
    {
        return route('blog.category', $this->slugIn(app(LocalizedUrls::class)->current()) ?? $this->slug);
    }
}
