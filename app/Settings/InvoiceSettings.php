<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Doklady: číselné rady a automatické vystavovanie.
 */
class InvoiceSettings extends Settings
{
    /** Predpona čísla faktúry, napr. „FA" → FA202600001. Môže byť prázdna. */
    public string $invoice_prefix;

    public string $proforma_prefix;

    public string $credit_note_prefix;

    /** Splatnosť v dňoch. */
    public int $due_days;

    /** Pri objednávke s platbou prevodom vystaviť zálohovú faktúru a priložiť ju k potvrdeniu. */
    public bool $issue_proforma;

    /** Po zaplatení objednávky vystaviť faktúru a poslať ju zákazníkovi. */
    public bool $issue_invoice_when_paid;

    /** Text v päte dokladov (napr. zápis v registri, poďakovanie). */
    public ?string $footer;

    public static function group(): string
    {
        return 'invoices';
    }
}
