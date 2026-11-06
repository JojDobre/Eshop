<?php

namespace App\Core\Installer;

use App\Core\Eshop;
use PDO;
use PDOException;

/**
 * Overí prístup k databáze MySQL / MariaDB zadaný v inštalátore.
 */
class DatabaseChecker
{
    /**
     * @param  array{host: string, port: int|string, database: string, username: string, password: ?string, prefix?: ?string}  $config
     */
    public function check(array $config): DatabaseCheckResult
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            (int) $config['port'],
            $config['database'],
        );

        try {
            $pdo = new PDO($dsn, $config['username'], (string) ($config['password'] ?? ''), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
        } catch (PDOException $e) {
            return DatabaseCheckResult::failed($this->friendlyError($e));
        }

        $version = (string) $pdo->query('SELECT VERSION()')->fetchColumn();
        $isMariaDb = stripos($version, 'mariadb') !== false;
        $numeric = preg_match('/^(\d+\.\d+\.\d+)/', $version, $m) ? $m[1] : '0.0.0';

        $minimum = $isMariaDb ? Eshop::MIN_MARIADB_VERSION : Eshop::MIN_MYSQL_VERSION;
        $supported = version_compare($numeric, $minimum, '>=');

        // MySQL 5.7 má iba obmedzenú podporu: inštalácia je povolená, ale zobrazí sa upozornenie.
        $limited = ! $isMariaDb && ! $supported && version_compare($numeric, '5.7.0', '>=');

        if (! $supported && ! $limited) {
            return DatabaseCheckResult::failed(
                __('installer.database.unsupported_version', ['version' => $version, 'minimum' => ($isMariaDb ? 'MariaDB ' : 'MySQL ').$minimum]),
                $version,
            );
        }

        $prefix = (string) ($config['prefix'] ?? '');
        $statement = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?');
        $statement->execute([$config['database'], $prefix.'migrations']);
        $alreadyInstalled = (int) $statement->fetchColumn() > 0;

        return new DatabaseCheckResult(
            success: true,
            version: $version,
            limitedSupport: $limited,
            containsInstallation: $alreadyInstalled,
        );
    }

    private function friendlyError(PDOException $e): string
    {
        $code = (int) ($e->errorInfo[1] ?? 0);

        return match ($code) {
            1045 => __('installer.database.error_access'),
            1049 => __('installer.database.error_database'),
            2002, 2005 => __('installer.database.error_host'),
            default => __('installer.database.error_generic', ['message' => $e->getMessage()]),
        };
    }
}
