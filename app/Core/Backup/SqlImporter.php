<?php

namespace App\Core\Backup;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;

/**
 * Načíta SQL súbor zo zálohy príkaz po príkaze (bez načítania celého súboru do pamäte).
 * Bodkočiarka v texte (napr. v popise produktu) príkaz neukončí.
 */
class SqlImporter
{
    /**
     * @param  resource  $stream
     * @return int počet vykonaných príkazov
     */
    public function import($stream, ?Connection $connection = null): int
    {
        $connection ??= DB::connection();
        $count = 0;
        $statement = '';
        $quote = null;
        $escaped = false;
        // MySQL escapuje lomítkom (\'), SQLite zdvojením úvodzovky ('').
        $backslashEscapes = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true);

        while (! feof($stream)) {
            $chunk = (string) fread($stream, 1 << 16);
            $length = strlen($chunk);
            $start = 0;

            for ($i = 0; $i < $length; $i++) {
                $char = $chunk[$i];

                if ($escaped) {
                    $escaped = false;

                    continue;
                }

                if ($quote !== null) {
                    if ($backslashEscapes && $char === '\\' && $quote !== '`') {
                        $escaped = true;
                    } elseif ($char === $quote) {
                        $quote = null;
                    }

                    continue;
                }

                if ($char === "'" || $char === '"' || $char === '`') {
                    $quote = $char;
                } elseif ($char === ';') {
                    $statement .= substr($chunk, $start, $i - $start);
                    $start = $i + 1;
                    $count += $this->run($connection, $statement);
                    $statement = '';
                }
            }

            $statement .= substr($chunk, $start);
        }

        return $count + $this->run($connection, $statement);
    }

    private function run(Connection $connection, string $statement): int
    {
        $statement = trim($statement);

        if ($statement === '') {
            return 0;
        }

        // SET príkazy MySQL v SQLite (testy) nemajú význam.
        if ($connection->getDriverName() === 'sqlite' && str_starts_with(strtoupper($statement), 'SET ')) {
            return 0;
        }

        $connection->unprepared($statement);

        return 1;
    }
}
