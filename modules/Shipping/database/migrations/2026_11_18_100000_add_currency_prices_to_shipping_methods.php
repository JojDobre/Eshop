<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ručne zadané ceny v ďalších menách: {"CZK": {"price": 9900, "free_from": 150000}}.
        // Čo chýba, prepočíta sa kurzom.
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->json('currency_prices')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn('currency_prices');
        });
    }
};
