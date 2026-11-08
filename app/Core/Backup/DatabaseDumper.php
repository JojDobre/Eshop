<?php

namespace App\Core\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use PDO;
use RuntimeException;

/**
 * Export databázy do SQL v čistom PHP (webhostingy často nemajú mysqldump ani možnosť spúšťať programy).
 * Zapisuje priebežne do súboru a číta po dávkach, aby stačila aj malá pamäť.
 */
class DatabaseDumper
{
    private const CHUNK = 500;

    public function dump(string $path, ?Connection $connection = null): void
    {
        $connection ??= DB::connection();
        $handle = fopen($path, 'wb') ?: throw new RuntimeException("Cannot write {$path}.");
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        fwrite($handle, "-- E-shop database backup\n-- ".now()->toIso8601String()."\n\n");

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
        }

        foreach ($this->tables($connection) as $table) {
            fwrite($handle, $this->createStatement($connection, $table).";\n\n");
            $this->writeRows($handle, $pdo, $table, $driver);
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        }

        fclose($handle);
    }

    /**
     * Tabuľky tejto inštalácie (s prefixom, ak je nastavený).
     *
     * @return list<string>
     */
    /**
     * Iba tabuľky databázy obchodu – na zdieľanom hostingu môže mať používateľ prístup aj k iným databázam.
     */
    public static function schema(Connection $connection): ?string
    {
        return in_array($connection->getDriverName(), ['mysql', 'mariadb'], true) ? $connection->getDatabaseName() : null;
    }

    private function tables(Connection $connection): array
    {
        $prefix = $connection->getTablePrefix();

        return collect($connection->getSchemaBuilder()->getTables(self::schema($connection)))
            ->pluck('name')
            ->filter(fn (string $name) => ($prefix === '' || str_starts_with($name, $prefix)) && ! str_starts_with($name, 'sqlite_'))
            ->sort()
            ->values()
            ->all();
    }

    private function createStatement(Connection $connection, string $table): string
    {
        $quoted = $this->quoteIdentifier($table, $connection->getDriverName());

        if ($connection->getDriverName() === 'sqlite') {
            $sql = $connection->selectOne('SELECT sql FROM sqlite_master WHERE type = ? AND name = ?', ['table', $table])->sql;

            return "DROP TABLE IF EXISTS {$quoted};\n{$sql}";
        }

        $row = (array) $connection->selectOne("SHOW CREATE TABLE {$quoted}");

        return "DROP TABLE IF EXISTS {$quoted};\n".array_values($row)[1];
    }

    /**
     * @param  resource  $handle
     */
    private function writeRows($handle, PDO $pdo, string $table, string $driver): void
    {
        $quoted = $this->quoteIdentifier($table, $driver);
        $offset = 0;

        do {
            $rows = $pdo->query("SELECT * FROM {$quoted} LIMIT ".self::CHUNK." OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);

            if ($rows === []) {
                break;
            }

            $columns = implode(', ', array_map(fn (string $column) => $this->quoteIdentifier($column, $driver), array_keys($rows[0])));
            $values = array_map(fn (array $row) => '('.implode(', ', array_map(fn ($value) => $this->quoteValue($pdo, $value), $row)).')', $rows);

            fwrite($handle, "INSERT INTO {$quoted} ({$columns}) VALUES\n".implode(",\n", $values).";\n");
            $offset += self::CHUNK;
        } while (count($rows) === self::CHUNK);

        fwrite($handle, "\n");
    }

    private function quoteValue(PDO $pdo, mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_int($value), is_float($value) => (string) $value,
            default => $pdo->quote((string) $value),
        };
    }

    private function quoteIdentifier(string $name, string $driver): string
    {
        return $driver === 'sqlite' ? '"'.str_replace('"', '""', $name).'"' : '`'.str_replace('`', '``', $name).'`';
    }
}
