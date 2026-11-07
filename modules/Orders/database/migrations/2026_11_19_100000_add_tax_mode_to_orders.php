<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ako sa objednávka zdanila: tuzemsko, OSS (DPH krajiny doručenia), prenesenie daňovej povinnosti.
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tax_mode', 16)->default('domestic')->after('vat_payer');
            $table->char('tax_country', 2)->nullable()->after('tax_mode');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['tax_mode', 'tax_country']);
        });
    }
};
