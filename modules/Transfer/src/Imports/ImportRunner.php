<?php

namespace Modules\Transfer\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Transfer\Models\DataImport;
use Throwable;

/**
 * Spracuje import po častiach: v jednej požiadavke najviac do časového limitu, ďalej pokračuje ďalšia.
 */
final class ImportRunner
{
    public const MAX_ERRORS = 200;

    public function __construct(private readonly Importers $importers) {}

    /**
     * Priradenie stĺpcov súboru k poliam podľa názvu (kľúč alebo názov poľa, bez diakritiky).
     *
     * @param  list<string>  $header
     * @return array<string, string> stĺpec → pole
     */
    public function guessMapping(Importer $importer, array $header): array
    {
        $normalize = fn (string $value) => Str::of($value)->ascii()->lower()->replaceMatches('/[^a-z0-9:]+/', '')->toString();
        // Kľúč poľa, jeho názov aj časti názvu („Názov / meno“ → „Názov“, „meno“).
        $fields = collect($importer->fields())->flatMap(fn ($label, $key) => collect([$key, $label, ...explode('/', (string) $label)])
            ->mapWithKeys(fn ($alias) => [$normalize(trim((string) $alias)) => $key]))->filter(fn ($key, $alias) => $alias !== '');

        return collect($header)->mapWithKeys(fn ($column) => [$column => $fields[$normalize($column)] ?? ''])->all();
    }

    /**
     * Riadok súboru prevedený na polia podľa priradenia.
     *
     * @param  array<string, string>  $row
     * @param  array<string, string>  $mapping
     * @return array<string, string>
     */
    public static function mapRow(array $row, array $mapping): array
    {
        $data = [];

        foreach ($mapping as $column => $field) {
            if ($field !== '' && $field !== null && array_key_exists($column, $row)) {
                $data[$field] = $row[$column];
            }
        }

        return $data;
    }

    /**
     * Náhľad prvých riadkov s chybami.
     *
     * @return list<array{row: int, data: array<string, string>, errors: list<string>}>
     */
    public function preview(DataImport $import, int $limit = 5): array
    {
        $importer = $this->importers->find($import->entity);
        $preview = [];

        foreach (SpreadsheetReader::rows($this->path($import)) as $index => $row) {
            $data = self::mapRow($row, (array) $import->mapping);
            $preview[] = ['row' => $index + 2, 'data' => $data, 'errors' => $importer->validate($data)];

            if (count($preview) >= $limit) {
                break;
            }
        }

        return $preview;
    }

    /**
     * Spracuje ďalšiu časť. Vráti true, keď je import hotový.
     */
    public function process(DataImport $import, float $seconds = 10.0): bool
    {
        $importer = $this->importers->find($import->entity);
        $started = microtime(true);
        $errors = (array) $import->errors;
        $processed = (int) $import->processed_rows;
        $created = (int) $import->created_count;
        $updated = (int) $import->updated_count;
        $finished = true;

        foreach (SpreadsheetReader::rows($this->path($import), $processed) as $index => $row) {
            if (microtime(true) - $started > $seconds) {
                $finished = false;
                break;
            }

            $data = self::mapRow($row, (array) $import->mapping);
            $rowErrors = $importer->validate($data);

            if ($rowErrors === []) {
                try {
                    DB::transaction(function () use ($importer, $data, &$created, &$updated): void {
                        $importer->import($data) ? $created++ : $updated++;
                    });
                } catch (Throwable $e) {
                    report($e);
                    $rowErrors = [$e->getMessage()];
                }
            }

            if ($rowErrors !== [] && count($errors) < self::MAX_ERRORS) {
                $errors[] = __('transfer::admin.import.row', ['row' => $index + 2]).': '.implode(' ', $rowErrors);
            }

            $processed = $index + 1;
        }

        $import->update([
            'processed_rows' => $finished ? $import->total_rows : $processed,
            'created_count' => $created,
            'updated_count' => $updated,
            'errors' => $errors,
            'status' => $finished ? 'done' : 'running',
        ]);

        return $finished;
    }

    public function path(DataImport $import): string
    {
        return storage_path('app/private/'.$import->file_path);
    }
}
