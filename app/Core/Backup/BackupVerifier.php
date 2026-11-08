<?php

namespace App\Core\Backup;

use App\Settings\SystemSettings;
use Throwable;

/**
 * Test obnovy: overí, že záloha sa dá otvoriť (aj s heslom), že obsahuje databázu s hlavnými tabuľkami
 * a že sa všetky súbory dajú prečítať (kontrolný súčet CRC v ZIP). Výsledok sa uloží do nastavení.
 */
class BackupVerifier
{
    /** Tabuľky, bez ktorých obnova nemá zmysel. */
    private const REQUIRED_TABLES = ['users', 'settings', 'migrations'];

    public function __construct(private readonly BackupManager $backups) {}

    /**
     * @return array{name: string, ok: bool, message: string, checked_at: string}
     */
    public function verify(string $name, ?string $password = null): array
    {
        try {
            $zip = $this->backups->open($name, $password);

            try {
                $stream = $zip->getStream('database.sql');

                if ($stream === false) {
                    throw new BackupException(__('admin.backups.check_no_database'));
                }

                $tables = [];

                while (($line = fgets($stream)) !== false) {
                    if (preg_match('/^CREATE TABLE (?:IF NOT EXISTS )?[`"]?([A-Za-z0-9_]+)[`"]?/i', $line, $match)) {
                        $tables[] = $match[1];
                    }
                }

                fclose($stream);

                $prefix = (string) config('database.connections.'.config('database.default').'.prefix');
                $missing = array_diff(array_map(fn ($table) => $prefix.$table, self::REQUIRED_TABLES), $tables);

                if ($missing !== []) {
                    throw new BackupException(__('admin.backups.check_missing_tables', ['tables' => implode(', ', $missing)]));
                }

                // Prečítanie každého súboru overí CRC (poškodený alebo neúplný ZIP zlyhá).
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entry = $zip->getStream((string) $zip->getNameIndex($i));

                    if ($entry === false) {
                        throw new BackupException(__('admin.backups.check_unreadable_file', ['file' => $zip->getNameIndex($i)]));
                    }

                    while (! feof($entry)) {
                        if (fread($entry, 1 << 20) === false) {
                            throw new BackupException(__('admin.backups.check_unreadable_file', ['file' => $zip->getNameIndex($i)]));
                        }
                    }

                    fclose($entry);
                }

                $result = ['ok' => true, 'message' => __('admin.backups.check_ok', ['tables' => count($tables), 'files' => $zip->numFiles])];
            } finally {
                $zip->close();
            }
        } catch (Throwable $e) {
            $result = ['ok' => false, 'message' => $e->getMessage()];
        }

        $result = ['name' => $name, ...$result, 'checked_at' => now()->toIso8601String()];
        $settings = app(SystemSettings::class);
        $settings->backups_last_check = $result;
        $settings->save();

        return $result;
    }
}
