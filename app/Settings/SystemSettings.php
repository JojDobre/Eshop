<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SystemSettings extends Settings
{
    /** Automatická denná záloha (cez cron). */
    public bool $backups_enabled;

    /** Počet uchovávaných záloh. */
    public int $backups_keep;

    /** Zahrnúť do záloh nahrané súbory (obrázky). */
    public bool $backups_include_files;

    public static function group(): string
    {
        return 'system';
    }
}
