<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SystemSettings extends Settings
{
    /** Automatická záloha (cez cron). */
    public bool $backups_enabled;

    /** Počet uchovávaných záloh. */
    public int $backups_keep;

    /** Zahrnúť do záloh nahrané súbory (obrázky). */
    public bool $backups_include_files;

    /** Ako často: daily, weekly. */
    public string $backups_frequency;

    /** Heslo na šifrovanie zálohy (prázdne = bez šifrovania). */
    public ?string $backups_password;

    /** Externé úložisko: none, ftp, sftp, s3. */
    public string $backups_remote;

    public ?string $backups_remote_host;

    public ?int $backups_remote_port;

    /** Používateľ (FTP, SFTP) alebo prístupový kľúč (S3). */
    public ?string $backups_remote_username;

    /** Heslo (FTP, SFTP) alebo tajný kľúč (S3). */
    public ?string $backups_remote_password;

    /** Adresár na úložisku. */
    public ?string $backups_remote_root;

    public ?string $backups_remote_bucket;

    public ?string $backups_remote_region;

    /** Adresa S3-kompatibilného úložiska (napr. Wasabi, Backblaze, MinIO). */
    public ?string $backups_remote_endpoint;

    public int $backups_remote_keep;

    /** Výsledok poslednej kontroly zálohy: name, ok, message, checked_at. */
    public ?array $backups_last_check;

    public static function group(): string
    {
        return 'system';
    }

    public static function encrypted(): array
    {
        return ['backups_password', 'backups_remote_password'];
    }
}
