<?php

namespace Modules\Accounting;

final class AccountingExporters
{
    /** @var array<string, AccountingExporter> */
    private array $exporters = [];

    public function register(AccountingExporter $exporter): void
    {
        $this->exporters[$exporter->key()] = $exporter;
    }

    public function find(string $key): ?AccountingExporter
    {
        return $this->exporters[$key] ?? null;
    }

    /**
     * @return array<string, AccountingExporter>
     */
    public function all(): array
    {
        return $this->exporters;
    }
}
