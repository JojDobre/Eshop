<?php

namespace Modules\Payments\Qr;

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

    public function normalizedIban(): string
    {
        return strtoupper((string) preg_replace('/\s+/', '', $this->iban));
    }

    public function decimalAmount(): string
    {
        return number_format($this->amount / 100, 2, '.', '');
    }
}
