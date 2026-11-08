<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Čiarový kód EAN/GTIN (XML feedy, identifikácia produktu podľa GPSR).
        Schema::table('products', fn (Blueprint $table) => $table->string('ean', 14)->nullable()->after('sku'));
        Schema::table('product_variants', fn (Blueprint $table) => $table->string('ean', 14)->nullable()->after('sku'));
    }

    public function down(): void
    {
        Schema::table('product_variants', fn (Blueprint $table) => $table->dropColumn('ean'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('ean'));
    }
};
