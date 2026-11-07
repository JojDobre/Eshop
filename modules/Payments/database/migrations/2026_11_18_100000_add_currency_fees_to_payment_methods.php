<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ručne zadané poplatky v ďalších menách: {"CZK": 3900}. Čo chýba, prepočíta sa kurzom.
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->json('currency_fees')->nullable()->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('currency_fees');
        });
    }
};
