<?php

namespace Modules\Transfer\Imports;

use Generator;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Riadky z CSV (oddeľovač a kódovanie sa zistia automaticky) alebo XLSX (prvý hárok).
 * Prvý riadok je hlavička; ďalšie riadky sa vracajú ako [názov stĺpca => hodnota].
 */
final class SpreadsheetReader
{
    /**
     * @return list<string>
     */
    public static function header(string $path): array
    {
        foreach (self::raw($path) as $row) {
            return array_map(fn ($value) => trim((string) $value), $row);
        }

        return [];
    }

    /**
     * @return Generator<int, array<string, string>> číslo riadku dát (od 0) → riadok
     */
    public static function rows(string $path, int $offset = 0): Generator
    {
        $header = null;
        $index = -1;

        foreach (self::raw($path) as $row) {
            if ($header === null) {
                $header = array_map(fn ($value) => trim((string) $value), $row);

                continue;
            }

            $index++;

            if ($index < $offset || array_filter($row, fn ($value) => trim((string) $value) !== '') === []) {
                continue;
            }

            $values = [];

            foreach ($header as $position => $name) {
                $values[$name] = trim((string) ($row[$position] ?? ''));
            }

            yield $index => $values;
        }
    }

    public static function count(string $path): int
    {
        $count = 0;

        foreach (self::rows($path) as $_) {
            $count++;
        }

        return $count;
    }

    /**
     * @return Generator<int, list<mixed>>
     */
    private static function raw(string $path): Generator
    {
        if (str_ends_with(strtolower($path), '.xlsx')) {
            $reader = new XlsxReader;
            $reader->open($path);

            try {
                foreach ($reader->getSheetIterator() as $sheet) {
                    foreach ($sheet->getRowIterator() as $row) {
                        yield array_map(fn ($value) => $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value, $row->toArray());
                    }

                    break;
                }
            } finally {
                $reader->close();
            }

            return;
        }

        $handle = fopen($path, 'r');
        $first = (string) fgets($handle);
        rewind($handle);

        // Oddeľovač podľa prvého riadku, kódovanie: UTF-8 (aj s BOM), inak Windows-1250.
        $delimiter = collect([';' => substr_count($first, ';'), ',' => substr_count($first, ','), "\t" => substr_count($first, "\t")])->sortDesc()->keys()->first();
        $utf8 = mb_check_encoding((string) file_get_contents($path, length: 65536), 'UTF-8');

        try {
            while (($row = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
                if (isset($row[0])) {
                    $row[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $row[0]);
                }

                yield $utf8 ? $row : array_map(fn ($value) => (string) iconv('CP1250', 'UTF-8//IGNORE', (string) $value), $row);
            }
        } finally {
            fclose($handle);
        }
    }
}
