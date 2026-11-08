<?php

namespace Modules\Blog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Verzia článku (história zmien s možnosťou obnovenia).
 *
 * @property int $id
 * @property int $version
 * @property array<string, string> $name
 * @property array<string, mixed>|null $blocks
 */
class PostVersion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'blog_post_versions';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['name' => 'array', 'excerpt' => 'array', 'blocks' => 'array'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
