<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            // Ceny podľa hmotnosti: [{"up_to": 5000, "price": 490}, …] (gramy, najmenšie jednotky meny).
            // Ak je objednávka ťažšia ako posledné pásmo, doprava sa neponúkne.
            $table->json('weight_rates')->nullable()->after('free_from');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_methods', fn (Blueprint $table) => $table->dropColumn('weight_rates'));
    }
};
