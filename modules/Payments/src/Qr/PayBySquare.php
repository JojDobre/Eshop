<?php

namespace Modules\Payments\Qr;

/**
 * PAY by square – slovenský štandard QR platieb (podporujú ho všetky slovenské banky).
 *
 * Údaje oddelené tabulátorom → CRC32 + údaje → LZMA1 → hlavička + dĺžka → base32hex.
 */
final class PayBySquare
{
    private const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUV';

    public static function encode(PaymentQrData $data): string
    {
        $fields = [
            '',                                   // číslo faktúry
            '1',                                  // počet platieb
            '1',                                  // platobný príkaz
            $data->decimalAmount(),
            $data->currency,
            $data->dueDate?->format('Ymd') ?? '',
            self::digits($data->variableSymbol, 10),
            '',                                   // konštantný symbol
            '',                                   // špecifický symbol
            '',                                   // referencia platiteľa
            self::clean($data->message, 140),
            '1',                                  // počet účtov
            $data->normalizedIban(),
            strtoupper((string) $data->bic),
            '0',                                  // trvalý príkaz
            '0',                                  // inkaso
            self::clean($data->beneficiaryName, 70),
            '',                                   // adresa príjemcu 1
            '',                                   // adresa príjemcu 2
        ];

        $payload = implode("\t", $fields);
        $payload = pack('V', crc32($payload)).$payload;

        $binary = "\x00\x00".pack('v', strlen($payload)).LzmaLiteralEncoder::compress($payload);

        return self::base32hex($binary);
    }

    private static function base32hex(string $binary): string
    {
        $bits = '';

        foreach (str_split($binary) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $bits = str_pad($bits, (int) ceil(strlen($bits) / 5) * 5, '0');

        return implode('', array_map(fn (string $chunk) => self::ALPHABET[bindec($chunk)], str_split($bits, 5)));
    }

    private static function digits(string $value, int $max): string
    {
        return substr((string) preg_replace('/\D/', '', $value), 0, $max);
    }

    private static function clean(string $value, int $max): string
    {
        return mb_substr(trim((string) preg_replace('/[\t\r\n]+/', ' ', $value)), 0, $max);
    }
}
