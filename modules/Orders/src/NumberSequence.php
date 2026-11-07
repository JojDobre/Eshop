<?php

namespace Modules\Orders;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Číselný rad bez medzier a duplicít ani pri súbežných objednávkach (zámok riadku v databáze).
 * Musí sa volať vnútri databázovej transakcie.
 */
class NumberSequence
{
    public function next(string $key): int
    {
        $row = DB::table('number_sequences')->where('key', $key)->lockForUpdate()->first();

        if ($row === null) {
            try {
                DB::table('number_sequences')->insert(['key' => $key, 'value' => 1]);

                return 1;
            } catch (QueryException) {
                // Rad medzitým vytvorila iná požiadavka.
                $row = DB::table('number_sequences')->where('key', $key)->lockForUpdate()->first();
            }
        }

        $value = (int) $row->value + 1;
        DB::table('number_sequences')->where('key', $key)->update(['value' => $value]);

        return $value;
    }

    /**
     * Číslo objednávky v tvare RRRRNNNNN, napr. 202600001 (zároveň variabilný symbol).
     */
    public function nextOrderNumber(): string
    {
        $year = now()->format('Y');

        return $year.str_pad((string) $this->next('orders-'.$year), 5, '0', STR_PAD_LEFT);
    }
}
