<?php

namespace Modules\Transfer\Imports;

/**
 * Import jednej entity: polia, kontrola riadku a uloženie (vytvorenie alebo aktualizácia).
 */
interface Importer
{
    public function key(): string;

    public function label(): string;

    /**
     * Polia, do ktorých sa dajú priradiť stĺpce súboru (kľúč → názov).
     *
     * @return array<string, string>
     */
    public function fields(): array;

    /** Pole, podľa ktorého sa hľadá existujúci záznam (napr. sku, email). */
    public function keyField(): string;

    /**
     * Chyby riadku (prázdne = v poriadku).
     *
     * @param  array<string, string>  $data  pole → hodnota
     * @return list<string>
     */
    public function validate(array $data): array;

    /**
     * Uloží riadok. Vráti true pri novom zázname, false pri aktualizácii.
     *
     * @param  array<string, string>  $data
     */
    public function import(array $data): bool;
}
