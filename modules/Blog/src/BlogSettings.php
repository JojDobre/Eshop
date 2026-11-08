<?php

namespace Modules\Blog;

use Spatie\LaravelSettings\Settings;

class BlogSettings extends Settings
{
    /** Komentáre pod článkami (dajú sa vypnúť aj pri jednotlivom článku). */
    public bool $comments_enabled;

    /** Komentár sa zobrazí až po schválení v administrácii. */
    public bool $comments_require_approval;

    public int $posts_per_page;

    public static function group(): string
    {
        return 'blog';
    }
}
