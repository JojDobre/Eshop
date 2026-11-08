<?php

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $blog_post_id
 * @property string $name
 * @property string $email
 * @property string $content
 * @property bool $is_approved
 */
class PostComment extends Model
{
    protected $table = 'blog_comments';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_approved' => 'boolean'];
    }

    /**
     * @return BelongsTo<Post, $this>
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'blog_post_id');
    }

    /**
     * @param  Builder<PostComment>  $query
     */
    public function scopeApproved(Builder $query): void
    {
        $query->where('is_approved', true);
    }
}
