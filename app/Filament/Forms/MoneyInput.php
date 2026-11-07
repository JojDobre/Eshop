<?php

namespace App\Filament\Forms;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use Filament\Forms\Components\TextInput;

/**
 * Pole na sumu: zobrazuje desatinné číslo (4,90), ukladá najmenšie jednotky meny (490).
 */
final class MoneyInput
{
    /** Kladná suma s najviac dvoma desatinnými miestami, s bodkou alebo čiarkou (12,90 aj 12.90). */
    public const RULE = 'regex:/^\\s*\\d{1,9}(?:[.,]\\d{1,2})?\\s*$/';

    public static function make(string $name, ?string $currency = null): TextInput
    {
        $currency ??= app(CurrencyContext::class)->current();

        return TextInput::make($name)
            ->rule(self::RULE)
            ->inputMode('decimal')
            ->suffix($currency)
            ->formatStateUsing(fn ($state) => is_numeric($state) ? Price::toDecimal((int) $state, $currency) : $state)
            ->dehydrateStateUsing(fn ($state) => filled($state) ? Price::toMinor((string) $state, $currency) : null);
    }
}
