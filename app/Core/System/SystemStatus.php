<?php

namespace App\Core\System;

use App\Core\Backup\BackupManager;
use App\Core\Eshop;
use App\Core\Installer\Requirement;
use App\Core\Installer\RequirementsChecker;
use App\Core\Modules\ModuleManager;
use App\Settings\MailSettings;
use App\Settings\SystemSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Number;
use Throwable;

/**
 * Kontroly prevádzky pre stránku Systém → Stav systému.
 */
class SystemStatus
{
    public const OK = 'ok';

    public const WARNING = 'warning';

    public const ERROR = 'error';

    public function __construct(
        private readonly RequirementsChecker $requirements,
        private readonly CronHeartbeat $cron,
        private readonly BackupManager $backups,
        private readonly ModuleManager $modules,
    ) {}

    /**
     * @return array<string, list<array{label: string, value: string, status: string, help?: string}>>
     */
    public function checks(): array
    {
        return [
            'application' => $this->application(),
            'operations' => $this->operations(),
            'server' => $this->server(),
        ];
    }

    /** Najhorší stav zo všetkých kontrol. */
    public function overall(): string
    {
        $statuses = collect($this->checks())->flatten(1)->pluck('status');

        return $statuses->contains(self::ERROR) ? self::ERROR : ($statuses->contains(self::WARNING) ? self::WARNING : self::OK);
    }

    /**
     * @return list<array{label: string, value: string, status: string, help?: string}>
     */
    private function application(): array
    {
        $t = fn (string $key, array $replace = []) => __('admin.status.'.$key, $replace);
        $problems = count($this->modules->problems());

        return [
            ['label' => $t('version'), 'value' => Eshop::VERSION, 'status' => self::OK],
            ['label' => $t('debug'), 'value' => config('app.debug') ? $t('on') : $t('off'),
                'status' => config('app.debug') ? self::ERROR : self::OK, 'help' => config('app.debug') ? $t('debug_help') : null],
            ['label' => $t('https'), 'value' => (string) config('app.url'),
                'status' => str_starts_with((string) config('app.url'), 'https://') ? self::OK : self::WARNING,
                'help' => str_starts_with((string) config('app.url'), 'https://') ? null : $t('https_help')],
            ['label' => $t('modules'), 'value' => $problems === 0 ? $t('modules_ok') : $t('modules_problems', ['count' => $problems]),
                'status' => $problems === 0 ? self::OK : self::ERROR],
            ['label' => $t('safe_mode'), 'value' => $this->modules->isSafeMode() ? $t('on') : $t('off'),
                'status' => $this->modules->isSafeMode() ? self::WARNING : self::OK],
        ];
    }

    /**
     * @return list<array{label: string, value: string, status: string, help?: string}>
     */
    private function operations(): array
    {
        $t = fn (string $key, array $replace = []) => __('admin.status.'.$key, $replace);
        $lastRun = $this->cron->lastRun();
        $mailer = $this->setting(MailSettings::class)?->mailer ?? 'log';
        $settings = $this->setting(SystemSettings::class);
        $lastBackup = $this->backups->all()[0] ?? null;
        $backupOld = $lastBackup === null || $lastBackup['created_at']->lessThan(now()->subDays(2));

        return [
            ['label' => $t('cron'), 'value' => $lastRun ? $t('cron_last', ['time' => $lastRun->diffForHumans()]) : $t('cron_never'),
                'status' => $this->cron->isRunning() ? self::OK : self::WARNING,
                'help' => $this->cron->isRunning() ? null : $t('cron_help')],
            ['label' => $t('mail'), 'value' => $mailer,
                'status' => $mailer === 'log' ? self::WARNING : self::OK, 'help' => $mailer === 'log' ? $t('mail_help') : null],
            ['label' => $t('backup'), 'value' => $lastBackup ? $lastBackup['created_at']->diffForHumans() : $t('backup_never'),
                'status' => ($settings?->backups_enabled ?? true) && $backupOld ? self::WARNING : self::OK,
                'help' => $backupOld ? $t('backup_help') : null],
        ];
    }

    /**
     * @return list<array{label: string, value: string, status: string, help?: string}>
     */
    private function server(): array
    {
        $t = fn (string $key, array $replace = []) => __('admin.status.'.$key, $replace);
        $rows = [
            ['label' => 'PHP', 'value' => PHP_VERSION, 'status' => self::OK],
            ['label' => $t('database'), 'value' => $this->databaseVersion(), 'status' => self::OK],
        ];

        foreach ($this->requirements->check() as $requirement) {
            /** @var Requirement $requirement */
            if ($requirement->key === 'php') {
                continue;
            }

            $rows[] = [
                'label' => $requirement->label,
                'value' => $requirement->current ?? ($requirement->passed ? 'OK' : $t('missing')),
                'status' => $requirement->passed ? self::OK : ($requirement->required ? self::ERROR : self::WARNING),
            ];
        }

        $free = function_exists('disk_free_space') ? @disk_free_space(storage_path()) : false;

        if ($free !== false) {
            $rows[] = ['label' => $t('disk_free'), 'value' => Number::fileSize((float) $free, 1),
                'status' => $free < 200 * 1024 * 1024 ? self::WARNING : self::OK];
        }

        return $rows;
    }

    private function databaseVersion(): string
    {
        try {
            $connection = DB::connection();

            return $connection->getDriverName().' '.($connection->getDriverName() === 'sqlite'
                ? $connection->selectOne('select sqlite_version() as v')->v
                : $connection->selectOne('select version() as v')->v);
        } catch (Throwable) {
            return '—';
        }
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    private function setting(string $class): ?object
    {
        try {
            return app($class);
        } catch (Throwable) {
            return null;
        }
    }
}
