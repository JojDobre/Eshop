<?php

namespace Modules\Invoices;

use Closure;
use Modules\Invoices\Models\Invoice;

/**
 * Zdroj QR kódu na platbu pre doklady. Platobný modul zaregistruje generátor
 * (napr. PAY by square pre Slovensko, QR Platba pre Česko).
 */
class PaymentQrRegistry
{
    /** @var (Closure(Invoice): ?string)|null */
    private ?Closure $generator = null;

    /**
     * @param  Closure(Invoice): ?string  $generator  Vráti obrázok QR kódu ako data URI.
     */
    public function register(Closure $generator): void
    {
        $this->generator = $generator;
    }

    public function dataUri(Invoice $invoice): ?string
    {
        return $this->generator !== null ? ($this->generator)($invoice) : null;
    }
}
