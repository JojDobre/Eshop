<?php

namespace Modules\Pages\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
use App\Core\Content\LocalizedUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /** Vyhlásenie o prístupnosti (Akt o prístupnosti, EAA). */
    public const ROLE_ACCESSIBILITY = 'accessibility';

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'slug', 'content', 'meta_title', 'meta_description', 'blocks'];

    protected $attributes = [
        'show_in_footer' => true,
        'position' => 0,
        'is_visible' => true,
    ];

    /** Stránky, ktorých znenie sa archivuje po verziách (zákazník s nimi súhlasí). */
    public const VERSIONED_ROLES = [self::ROLE_TERMS, self::ROLE_PRIVACY];

    protected static function booted(): void
    {
        static::saved(function (Page $page): void {
            if ($page->isVersioned() && ($page->wasRecentlyCreated || $page->wasChanged(['name', 'content', 'role']) || ! $page->versions()->exists())) {
                $page->createVersion();
            }
        });
    }

    /**
     * @return HasMany<PageVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PageVersion::class)->orderByDesc('version');
    }

    public function isVersioned(): bool
    {
        return in_array($this->role, self::VERSIONED_ROLES, true);
    }

    /**
     * Aktuálna verzia (pri staršej inštalácii bez verzií sa vytvorí prvá).
     */
    public function currentVersion(): PageVersion
    {
        return $this->versions()->first() ?? $this->createVersion();
    }

    private function createVersion(): PageVersion
    {
        $last = $this->versions()->first();

        // Nová verzia iba vtedy, keď sa text naozaj zmenil.
        if ($last !== null && $last->getTranslations('content') === $this->getTranslations('content') && $last->getTranslations('name') === $this->getTranslations('name')) {
            return $last;
        }

        return $this->versions()->create([
            'version' => ($last->version ?? 0) + 1,
            'name' => $this->getTranslations('name'),
            'content' => $this->getTranslations('content'),
        ]);
    }

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
        return route('pages.show', $this->slugIn(app(LocalizedUrls::class)->current()) ?? $this->slug);
    }
}
