<?php

namespace Modules\Transfer\Imports;

final class Importers
{
    /** @var array<string, Importer> */
    private array $importers = [];

    public function register(Importer $importer): void
    {
        $this->importers[$importer->key()] = $importer;
    }

    public function find(string $key): ?Importer
    {
        return $this->importers[$key] ?? null;
    }

    /**
     * @return array<string, Importer>
     */
    public function all(): array
    {
        return $this->importers;
    }
}
