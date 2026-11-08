<?php

namespace Modules\Blog\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
use App\Core\Content\LocalizedUrls;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

/**
 * Článok blogu. Obsah sa skladá z blokov (v každom jazyku), zverejnený je od published_at.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $excerpt
 * @property list<array<string, mixed>>|null $blocks
 * @property string|null $cover_image
 * @property int|null $blog_category_id
 * @property int|null $author_id
 * @property string $status
 * @property Carbon|null $published_at
 * @property bool $comments_enabled
 * @property string $preview_token
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property-read PostCategory|null $category
 * @property-read User|null $author
 */
class Post extends Model
{
    use HasContentTranslations, HasLocalizedSlug;

    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    /** Počet slov za minútu pre odhad času čítania. */
    public const WORDS_PER_MINUTE = 200;

    protected $table = 'blog_posts';

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'slug', 'excerpt', 'blocks', 'meta_title', 'meta_description'];

    protected $attributes = ['status' => self::DRAFT, 'comments_enabled' => true];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'comments_enabled' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            $post->preview_token ??= Str::random(40);
        });

        // História verzií: nová verzia pri zmene názvu, perexu alebo obsahu (najviac 20).
        static::saved(function (Post $post): void {
            if (! $post->wasRecentlyCreated && ! $post->wasChanged(['name', 'excerpt', 'blocks'])) {
                return;
            }

            $last = $post->versions()->first();

            if ($last !== null && $last->name == $post->getTranslations('name') && ($last->excerpt ?? []) == $post->getTranslations('excerpt')
                && ($last->blocks ?? []) == $post->getTranslations('blocks')) {
                return;
            }

            $post->versions()->create([
                'version' => (int) $post->versions()->max('version') + 1,
                'name' => $post->getTranslations('name'),
                'excerpt' => $post->getTranslations('excerpt'),
                'blocks' => $post->getTranslations('blocks'),
                'user_id' => auth()->id(),
            ]);

            $post->versions()->where('version', '<=', (int) $post->versions()->max('version') - 20)->delete();
        });
    }

    /**
     * Zverejnené články (koncepty a naplánované články sa nezobrazia).
     *
     * @param  Builder<Post>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::PUBLISHED)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === self::PUBLISHED && $this->published_at !== null && $this->published_at->lte(now());
    }

    public function isScheduled(): bool
    {
        return $this->status === self::PUBLISHED && $this->published_at !== null && $this->published_at->isFuture();
    }

    /**
     * @return BelongsTo<PostCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'blog_category_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return BelongsToMany<PostTag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PostTag::class, 'blog_post_tag', 'blog_post_id', 'blog_tag_id');
    }

    /**
     * Odporúčané produkty (karta s cenou a tlačidlom Do košíka).
     *
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'blog_post_product', 'blog_post_id', 'product_id')->withPivot('position')->orderByPivot('position');
    }

    /**
     * Kategórie katalógu, pri ktorých sa článok zobrazí ako súvisiaci.
     *
     * @return BelongsToMany<Category, $this>
     */
    public function catalogCategories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'blog_post_catalog_category', 'blog_post_id', 'category_id');
    }

    /**
     * @return HasMany<PostComment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class, 'blog_post_id');
    }

    /**
     * @return HasMany<PostVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PostVersion::class, 'blog_post_id')->orderByDesc('version');
    }

    public function url(): string
    {
        return route('blog.show', $this->slugIn(app(LocalizedUrls::class)->current()) ?? $this->slug);
    }

    public function previewUrl(): string
    {
        return route('blog.preview', $this->preview_token);
    }

    public function coverUrl(): ?string
    {
        return $this->cover_image ? Storage::disk('public')->url($this->cover_image) : null;
    }

    /**
     * Bloky v aktuálnom jazyku (chýbajúci preklad = prázdny zoznam).
     *
     * @return list<array<string, mixed>>
     */
    public function blockList(): array
    {
        $blocks = $this->blocks;

        return is_array($blocks) ? array_values(array_filter($blocks, 'is_array')) : [];
    }

    /** Text článku bez značiek (na čas čítania a popis). */
    public function plainText(): string
    {
        $parts = [(string) $this->excerpt];

        foreach ($this->blockList() as $block) {
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $parts[] = strip_tags((string) ($data['content'] ?? ''));
            $parts[] = (string) ($data['text'] ?? '');
        }

        return trim(preg_replace('/\s+/u', ' ', implode(' ', $parts)) ?? '');
    }

    /** Odhadovaný čas čítania v minútach. */
    public function readingMinutes(): int
    {
        $words = count(preg_split('/\s+/u', $this->plainText(), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return max(1, (int) ceil($words / self::WORDS_PER_MINUTE));
    }

    /**
     * Bloky s kotvami pri nadpisoch a obsah článku z nadpisov h2/h3 v textových blokoch.
     *
     * @return array{blocks: list<array<string, mixed>>, toc: list<array{id: string, text: string, level: int}>}
     */
    public function contentWithToc(): array
    {
        $toc = [];
        $used = [];
        $blocks = [];

        foreach ($this->blockList() as $block) {
            if (($block['type'] ?? null) === 'text' && is_string($block['data']['content'] ?? null)) {
                $block['data']['content'] = preg_replace_callback('/<h([23])([^>]*)>(.*?)<\/h\1>/is', function (array $match) use (&$toc, &$used): string {
                    $text = trim(html_entity_decode(strip_tags($match[3]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                    $id = Str::slug($text) ?: 'nadpis';
                    $base = $id;
                    $suffix = 2;

                    while (isset($used[$id])) {
                        $id = $base.'-'.$suffix++;
                    }

                    $used[$id] = true;
                    $toc[] = ['id' => $id, 'text' => $text, 'level' => (int) $match[1]];
                    $attributes = preg_replace('/\s+id="[^"]*"/i', '', $match[2]);

                    return '<h'.$match[1].$attributes.' id="'.$id.'">'.$match[3].'</h'.$match[1].'>';
                }, $block['data']['content']);
            }

            $blocks[] = $block;
        }

        return ['blocks' => $blocks, 'toc' => $toc];
    }

    /**
     * Obnoví názov, perex a obsah zo staršej verzie (vo všetkých jazykoch).
     */
    public function restoreVersion(PostVersion $version): void
    {
        $this->setTranslations('name', $version->name);
        $this->setTranslations('excerpt', $version->excerpt ?? []);
        $this->setTranslations('blocks', $version->blocks ?? []);
        $this->save();
    }
}
