<?php

namespace Modules\Payments\Qr;

use App\Core\Banking\BankAccount;

/**
 * Údaje platobného príkazu pre QR kód.
 */
final class PaymentQrData
{
    /**
     * @param  int  $amount  Suma v najmenších jednotkách meny.
     */
    public function __construct(
        public readonly string $iban,
        public readonly ?string $bic,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $variableSymbol,
        public readonly string $beneficiaryName,
        public readonly string $message = '',
        public readonly ?\DateTimeInterface $dueDate = null,
    ) {}

    /** Platný IBAN (aj prevedený z domáceho tvaru účtu), inak prázdny reťazec. */
    public function normalizedIban(): string
    {
        return BankAccount::iban($this->iban, $this->currency === 'EUR' ? 'SK' : 'CZ');
    }

    /** Platný SWIFT/BIC, inak null. */
    public function normalizedBic(): ?string
    {
        return BankAccount::bic($this->bic);
    }

    public function decimalAmount(): string
    {
        return number_format($this->amount / 100, 2, '.', '');
    }
}
