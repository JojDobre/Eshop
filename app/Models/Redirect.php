<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $from_path
 * @property string $to_url
 * @property int $status_code
 * @property bool $is_automatic
 * @property int $hits
 */
class Redirect extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'is_automatic' => 'boolean', 'hits' => 'integer', 'last_hit_at' => 'datetime'];
    }

    /** Cesta z adresy alebo cesty: „https://x.sk/a/b?c=1“ → „/a/b?c=1“. */
    public static function normalizePath(string $url): string
    {
        $parts = parse_url(trim($url));
        $path = '/'.ltrim(rawurldecode((string) ($parts['path'] ?? '')), '/');
        $path = $path === '/' ? $path : rtrim($path, '/');

        return isset($parts['query']) && $parts['query'] !== '' ? $path.'?'.$parts['query'] : $path;
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->from_path = self::normalizePath($redirect->from_path);
        });
    }

    /**
     * Zaznamená presmerovanie zo starej adresy na novú. Ak na starú adresu už niečo presmerúvalo,
     * reťaz sa skráti (A → B a B → C sa zmení na A → C) a cieľ nikdy nepresmeruje sám na seba.
     */
    public static function record(string $fromUrl, string $toUrl, bool $automatic = true): void
    {
        $from = self::normalizePath($fromUrl);
        $to = self::normalizePath($toUrl);

        if ($from === $to) {
            return;
        }

        static::query()->where('to_url', $fromUrl)->orWhere('to_url', $from)->update(['to_url' => $toUrl]);
        static::query()->where('from_path', $to)->delete();
        static::query()->updateOrCreate(['from_path' => $from], ['to_url' => $toUrl, 'status_code' => 301, 'is_automatic' => $automatic]);
    }
}
