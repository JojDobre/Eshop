<?php

namespace App\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Bloky úvodnej stránky („home“) alebo oblasti šablóny („area:{názov}“), v každom jazyku.
 *
 * @property int $id
 * @property string $key
 * @property list<array<string, mixed>>|null $blocks
 */
class BlockLayout extends Model
{
    use HasContentTranslations;

    public const HOME = 'home';

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['blocks'];

    /**
     * Bloky v aktuálnom jazyku (null = nenastavené).
     *
     * @return list<array<string, mixed>>|null
     */
    /** @var array<string, list<array<string, mixed>>> Neuložené bloky pre živý náhľad prispôsobenia (iba administrátor). */
    private static array $preview = [];

    /**
     * @param  list<array<string, mixed>>|null  $blocks
     */
    public static function preview(string $key, ?array $blocks): void
    {
        if ($blocks === null) {
            unset(self::$preview[$key]);
        } else {
            self::$preview[$key] = $blocks;
        }
    }

    public static function blocksFor(string $key): ?array
    {
        if (array_key_exists($key, self::$preview)) {
            return self::$preview[$key] !== [] ? array_values(self::$preview[$key]) : null;
        }

        try {
            if (! Schema::hasTable('block_layouts')) {
                return null;
            }

            $blocks = self::query()->where('key', $key)->first()?->blocks;
        } catch (Throwable) {
            return null;
        }

        return is_array($blocks) && $blocks !== [] ? array_values($blocks) : null;
    }
}
