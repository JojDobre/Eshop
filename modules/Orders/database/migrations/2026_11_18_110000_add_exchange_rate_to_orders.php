<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kurz meny objednávky voči predvolenej mene v čase nákupu (koľko jednotiek meny = 1 jednotka predvolenej).
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('exchange_rate', 18, 8)->default(1)->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });
    }
};
