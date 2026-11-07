<?php

namespace Modules\Payments\Qr;

use Illuminate\Support\Str;

/**
 * QR Platba (Short Payment Descriptor) – český štandard QR platieb.
 */
final class SpdQr
{
    public static function encode(PaymentQrData $data): string
    {
        $parts = [
            'SPD', '1.0',
            'ACC:'.$data->normalizedIban().($data->bic ? '+'.strtoupper($data->bic) : ''),
            'AM:'.$data->decimalAmount(),
            'CC:'.$data->currency,
        ];

        if (($vs = substr((string) preg_replace('/\D/', '', $data->variableSymbol), 0, 10)) !== '') {
            $parts[] = 'X-VS:'.$vs;
        }

        if ($data->dueDate) {
            $parts[] = 'DT:'.$data->dueDate->format('Ymd');
        }

        if (($name = self::clean($data->beneficiaryName, 35)) !== '') {
            $parts[] = 'RN:'.$name;
        }

        if (($message = self::clean($data->message, 60)) !== '') {
            $parts[] = 'MSG:'.$message;
        }

        return implode('*', $parts);
    }

    /**
     * Hviezdička oddeľuje polia; diakritika je povolená, ale bez nej je kód menší a spoľahlivejší.
     */
    private static function clean(string $value, int $max): string
    {
        return mb_substr(trim(str_replace('*', '', Str::ascii($value))), 0, $max);
    }
}
