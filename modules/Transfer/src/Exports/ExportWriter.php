<?php

namespace Modules\Transfer\Exports;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use XMLWriter;

/**
 * Zapíše riadky exportu do súboru: CSV (oddeľovač, kódovanie), XLSX, JSON alebo XML.
 */
final class ExportWriter
{
    public const FORMATS = ['csv', 'xlsx', 'json', 'xml'];

    /**
     * @param  array<string, string>  $header  kľúč stĺpca → názov
     * @param  iterable<array<string, mixed>>  $rows  riadky podľa kľúčov stĺpcov
     */
    public static function write(string $path, string $format, array $header, iterable $rows, string $delimiter = ';', string $encoding = 'UTF-8'): void
    {
        match ($format) {
            'xlsx' => self::xlsx($path, $header, $rows),
            'json' => self::json($path, $rows),
            'xml' => self::xml($path, $rows),
            default => self::csv($path, $header, $rows, $delimiter === 'tab' ? "\t" : $delimiter, $encoding),
        };
    }

    public static function extension(string $format): string
    {
        return in_array($format, self::FORMATS, true) ? $format : 'csv';
    }

    private static function csv(string $path, array $header, iterable $rows, string $delimiter, string $encoding): void
    {
        $out = fopen($path, 'w');
        $convert = fn (array $values) => $encoding === 'UTF-8' ? $values : array_map(fn ($value) => is_string($value) ? (string) iconv('UTF-8', 'CP1250//TRANSLIT', $value) : $value, $values);

        // BOM, aby Excel spoznal UTF-8.
        if ($encoding === 'UTF-8') {
            fwrite($out, "\xEF\xBB\xBF");
        }

        fputcsv($out, $convert(array_values($header)), $delimiter);

        foreach ($rows as $row) {
            fputcsv($out, $convert(array_map(fn ($value) => is_bool($value) ? (int) $value : $value, array_values($row))), $delimiter);
        }

        fclose($out);
    }

    private static function xlsx(string $path, array $header, iterable $rows): void
    {
        $writer = new XlsxWriter;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(array_values($header)));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues(array_map(fn ($value) => is_scalar($value) || $value === null ? $value : (string) $value, array_values($row))));
        }

        $writer->close();
    }

    private static function json(string $path, iterable $rows): void
    {
        $out = fopen($path, 'w');
        fwrite($out, '[');
        $first = true;

        foreach ($rows as $row) {
            fwrite($out, ($first ? "\n" : ",\n").json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $first = false;
        }

        fwrite($out, "\n]\n");
        fclose($out);
    }

    private static function xml(string $path, iterable $rows): void
    {
        $xml = new XMLWriter;
        $xml->openUri($path);
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('items');

        foreach ($rows as $row) {
            $xml->startElement('item');

            foreach ($row as $key => $value) {
                // Názov elementu z kľúča (field:farba → field_farba).
                $xml->writeElement((string) preg_replace('/[^A-Za-z0-9_]/', '_', (string) $key), (string) $value);
            }

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }
}
