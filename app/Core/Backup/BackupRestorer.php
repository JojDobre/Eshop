<?php

namespace App\Core\Backup;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Obnova zo zálohy: databáza, nahrané súbory a kľúč aplikácie.
 *
 * Pred obnovou sa vytvorí bezpečnostná záloha aktuálneho stavu, aby sa dala obnova vrátiť.
 * Kľúč aplikácie (APP_KEY) sa prevezme zo zálohy – inak by sa po obnove na čistom hostingu
 * nedali prečítať šifrované nastavenia (heslá a API kľúče).
 */
class BackupRestorer
{
    public function __construct(private readonly BackupManager $backups, private readonly SqlImporter $importer) {}

    /**
     * @return array{statements: int, files: int, safety_backup: ?string}
     */
    public function restore(string $name, bool $withFiles = true, bool $safetyBackup = true, ?string $password = null): array
    {
        $zip = $this->backups->open($name, $password);

        try {
            $stream = $zip->getStream('database.sql');

            if ($stream === false) {
                throw new BackupException(__('admin.backups.check_no_database'));
            }

            // Bezpečnostná záloha pred obnovou (bez súborov – tie sa iba prepíšu/doplnia).
            $safety = $safetyBackup ? $this->backups->create(false) : null;

            @set_time_limit(0);
            // Čistý stav: tabuľky novšie ako záloha by inak zablokovali doplnenie migrácií.
            Schema::disableForeignKeyConstraints();
            $this->dropAllTables();

            $statements = $this->importer->import($stream);
            fclose($stream);
            Schema::enableForeignKeyConstraints();

            $files = $withFiles ? $this->restoreUploads($zip) : 0;
            $this->restoreAppKey($zip);
        } finally {
            $zip->close();
        }

        try {
            // Záloha môže byť zo staršej verzie – doplnia sa chýbajúce migrácie.
            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('optimize:clear');
        } catch (Throwable $e) {
            report($e);
        }

        return ['statements' => $statements, 'files' => $files, 'safety_backup' => $safety];
    }

    /**
     * Zmaže všetky tabuľky – najprv tie, na ktoré už nič neodkazuje (funguje aj bez vypnutia cudzích kľúčov).
     */
    private function dropAllTables(): void
    {
        $prefix = DB::connection()->getTablePrefix();
        $tables = collect(Schema::getTables(DatabaseDumper::schema(DB::connection())))->pluck('name')
            ->filter(fn (string $name) => str_starts_with($name, $prefix) && ! str_starts_with($name, 'sqlite_'))
            ->map(fn (string $name) => substr($name, strlen($prefix)))
            ->values()->all();
        $references = [];

        foreach ($tables as $table) {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $foreign = (string) $foreignKey['foreign_table'];
                $foreign = str_starts_with($foreign, $prefix) ? substr($foreign, strlen($prefix)) : $foreign;

                if ($foreign !== $table) {
                    $references[$foreign][] = $table;
                }
            }
        }

        while ($tables !== []) {
            $free = array_values(array_filter($tables, fn (string $table) => array_intersect($references[$table] ?? [], $tables) === []));
            // Cyklické odkazy: zmaže zvyšok s vypnutými kontrolami.
            $batch = $free === [] ? $tables : $free;

            foreach ($batch as $table) {
                Schema::drop($table);
            }

            $tables = array_values(array_diff($tables, $batch));
        }
    }

    private function restoreUploads(\ZipArchive $zip): int
    {
        $count = 0;
        $root = public_path('uploads');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = (string) $zip->getNameIndex($i);

            // Iba súbory z uploads/, bez ../ a skrytých súborov okrem .htaccess.
            if (! str_starts_with($entry, 'uploads/') || str_ends_with($entry, '/') || str_contains($entry, '..')) {
                continue;
            }

            $target = $root.'/'.substr($entry, 8);
            File::ensureDirectoryExists(dirname($target));
            $source = $zip->getStream($entry);

            if ($source !== false) {
                $handle = fopen($target, 'wb');
                stream_copy_to_stream($source, $handle);
                fclose($handle);
                fclose($source);
                $count++;
            }
        }

        return $count;
    }

    private function restoreAppKey(\ZipArchive $zip): void
    {
        $env = $zip->getFromName('env.txt');
        $envPath = app()->environmentFilePath();

        if (! is_string($env) || ! preg_match('/^APP_KEY=(.+)$/m', $env, $match) || ! is_file($envPath)) {
            return;
        }

        $key = trim($match[1]);
        $current = (string) file_get_contents($envPath);

        if ($key === '' || $key === config('app.key')) {
            return;
        }

        file_put_contents($envPath, preg_match('/^APP_KEY=.*$/m', $current)
            ? (string) preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $current)
            : rtrim($current)."\nAPP_KEY={$key}\n");
        config(['app.key' => $key]);
    }
}
