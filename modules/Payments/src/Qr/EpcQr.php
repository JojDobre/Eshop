<?php

namespace Modules\Payments\Qr;

/**
 * EPC QR (SEPA platba) – európsky štandard pre platby v eurách.
 */
final class EpcQr
{
    public static function encode(PaymentQrData $data): string
    {
        $reference = trim(($data->variableSymbol !== '' ? 'VS'.$data->variableSymbol.' ' : '').$data->message);

        return implode("\n", [
            'BCD', '002', '1', 'SCT',
            strtoupper((string) $data->bic),
            mb_substr($data->beneficiaryName, 0, 70),
            $data->normalizedIban(),
            'EUR'.$data->decimalAmount(),
            '',
            '',
            mb_substr($reference, 0, 140),
        ]);
    }
}
