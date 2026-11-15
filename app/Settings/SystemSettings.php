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

    /** Externé úložisko: none, gdrive, ftp, sftp, s3. */
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

    /** Google Disk: OAuth klient z Google Cloud Console. */
    public ?string $backups_gdrive_client_id;

    public ?string $backups_gdrive_client_secret;

    /** Obnovovací token prepojeného Google účtu. */
    public ?string $backups_gdrive_refresh_token;

    /** Priečinok so zálohami na Disku (vytvorí sa automaticky). */
    public ?string $backups_gdrive_folder_id;

    /** E-mail prepojeného Google účtu (na zobrazenie). */
    public ?string $backups_gdrive_account;

    /** Výsledok poslednej kontroly zálohy: name, ok, message, checked_at. */
    public ?array $backups_last_check;

    /** Automaticky nainštalovať novú verziu (denná kontrola cez cron). */
    public bool $updates_auto_install;

    /** Poslať správcom e-mail o novej verzii. */
    public bool $updates_notify;

    /** Verzia, o ktorej už správcovia dostali e-mail. */
    public ?string $updates_notified_version;

    public static function group(): string
    {
        return 'system';
    }

    public static function encrypted(): array
    {
        return ['backups_password', 'backups_remote_password', 'backups_gdrive_client_secret', 'backups_gdrive_refresh_token'];
    }
}
