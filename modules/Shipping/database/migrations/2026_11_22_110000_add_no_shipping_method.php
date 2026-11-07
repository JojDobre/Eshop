<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // „Bez dopravy“ pre košík iba so službami alebo digitálnymi produktmi.
        // Ponúkne sa iba vtedy, keď v košíku nie je tovar na doručenie.
        // Iba pre existujúci obchod – pri novej inštalácii ju vytvorí inštalátor s ostatnou dopravou.
        if (DB::table('shipping_methods')->where('type', 'none')->exists() || ! DB::table('shipping_methods')->exists()) {
            return;
        }

        $currency = DB::table('settings')->where('group', 'general')->where('name', 'default_currency')->value('payload');

        DB::table('shipping_methods')->insert([
            'name' => json_encode(['sk' => 'Bez dopravy', 'cs' => 'Bez dopravy', 'en' => 'No shipping'], JSON_UNESCAPED_UNICODE),
            'description' => json_encode(['sk' => 'Služba alebo elektronický tovar – nič sa neposiela.', 'cs' => 'Služba nebo elektronické zboží – nic se neposílá.', 'en' => 'A service or electronic goods – nothing is shipped.'], JSON_UNESCAPED_UNICODE),
            'type' => 'none',
            'price' => 0,
            'currency' => $currency ? json_decode($currency) : 'EUR',
            'position' => 99,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('shipping_methods')->where('type', 'none')->delete();
    }
};
