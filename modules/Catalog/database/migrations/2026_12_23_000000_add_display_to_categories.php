<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Vzhľad kategórie: nadpis / banner s obrázkom / bez nadpisu, popis, omrvinková navigácia.
    public function up(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->json('display')->nullable());
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('display'));
    }
};
