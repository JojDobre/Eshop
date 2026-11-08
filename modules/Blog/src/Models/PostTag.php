<?php

namespace Modules\Blog\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
use App\Core\Content\LocalizedUrls;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 */
class PostTag extends Model
{
    use HasContentTranslations, HasLocalizedSlug;

    protected $table = 'blog_tags';

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'slug'];

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'blog_post_tag', 'blog_tag_id', 'blog_post_id');
    }

    public function url(): string
    {
        return route('blog.tag', $this->slugIn(app(LocalizedUrls::class)->current()) ?? $this->slug);
    }
}
