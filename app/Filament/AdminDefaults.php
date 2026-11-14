<?php

namespace App\Filament;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Table;

/**
 * Jednotné predvolené správanie polí a tabuliek v celej administrácii.
 */
final class AdminDefaults
{
    public static function register(): void
    {
        // Dátumy vo formáte podľa jazyka administrácie (napr. 9. 11. 2026) s kalendárom, nie natívne pole
        // prehliadača, ktoré ukazuje formát podľa jazyka systému (napr. 11/09/2026).
        $dateFormat = fn () => app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y';

        DatePicker::configureUsing(fn (DatePicker $picker) => $picker
            ->native(false)
            ->displayFormat($dateFormat)
            ->firstDayOfWeek(1)
            ->closeOnDateSelection());

        DateTimePicker::configureUsing(function (DateTimePicker $picker) use ($dateFormat): void {
            if ($picker instanceof DatePicker) {
                return;
            }

            $picker->native(false)
                ->displayFormat(fn () => $dateFormat().' H:i')
                ->seconds(false)
                ->firstDayOfWeek(1);
        });

        Table::configureUsing(fn (Table $table) => $table
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(25));
    }
}
