<?php

namespace App\Core\Banking;

/**
 * Číslo účtu pre QR platby: overí IBAN (kontrolné číslice) a český či slovenský tvar
 * „predčíslie-číslo/kód banky“ prevedie na IBAN. Neplatný účet vráti prázdny reťazec,
 * aby sa namiesto chybného QR kódu radšej nezobrazil žiadny.
 */
final class BankAccount
{
    public static function iban(?string $account, string $country = 'CZ'): string
    {
        $value = strtoupper((string) preg_replace('/\s+/', '', (string) $account));

        if ($value === '') {
            return '';
        }

        if (preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $value)) {
            return self::isValidIban($value) ? $value : '';
        }

        // Domáci tvar (CZ, SK): [predčíslie-]číslo/kód banky, napr. 19-2000145399/0800.
        if (preg_match('/^(?:(\d{1,6})-)?(\d{2,10})\/(\d{4})$/', $value, $match)) {
            $country = in_array($country, ['CZ', 'SK'], true) ? $country : 'CZ';
            $bban = $match[3].str_pad($match[1], 6, '0', STR_PAD_LEFT).str_pad($match[2], 10, '0', STR_PAD_LEFT);
            $check = 98 - self::mod97($bban.self::lettersToDigits($country).'00');

            return $country.str_pad((string) $check, 2, '0', STR_PAD_LEFT).$bban;
        }

        return '';
    }

    /** SWIFT/BIC iba v platnom tvare (8 alebo 11 znakov), inak null – QR platba sa bez neho zaobíde. */
    public static function bic(?string $bic): ?string
    {
        $value = strtoupper((string) preg_replace('/\s+/', '', (string) $bic));

        return preg_match('/^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/', $value) ? $value : null;
    }

    public static function isValidIban(string $iban): bool
    {
        $iban = strtoupper((string) preg_replace('/\s+/', '', $iban));

        if (! preg_match('/^[A-Z]{2}\d{2}[A-Z0-9]{10,30}$/', $iban)) {
            return false;
        }

        return self::mod97(self::lettersToDigits(substr($iban, 4).substr($iban, 0, 4))) === 1;
    }

    private static function lettersToDigits(string $value): string
    {
        return (string) preg_replace_callback('/[A-Z]/', fn (array $m) => (string) (ord($m[0]) - 55), $value);
    }

    private static function mod97(string $digits): int
    {
        $rest = 0;

        foreach (str_split($digits, 7) as $chunk) {
            $rest = (int) (($rest.$chunk) % 97);
        }

        return $rest;
    }
}
