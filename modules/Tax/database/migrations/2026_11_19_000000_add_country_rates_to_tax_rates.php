<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sadzby v iných krajinách EÚ pre režim OSS: {"CZ": 21, "DE": 19}.
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->json('country_rates')->nullable()->after('rate');
        });
    }

    public function down(): void
    {
        Schema::table('tax_rates', function (Blueprint $table) {
            $table->dropColumn('country_rates');
        });
    }
};
