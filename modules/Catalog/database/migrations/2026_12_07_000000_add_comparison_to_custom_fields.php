<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            // Skupina vlastností v porovnávači a parametroch (napr. „Rozmery“), v každom jazyku.
            $table->json('group')->nullable();
            // Najlepšia číselná hodnota v porovnávači: max (napr. výkon) alebo min (napr. hmotnosť).
            $table->string('compare_best', 8)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('custom_fields', fn (Blueprint $table) => $table->dropColumn(['group', 'compare_best']));
    }
};
