<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Vzhľad stránky: nadpis / banner s obrázkom / bez nadpisu, šírka, omrvinková navigácia.
    public function up(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->json('display')->nullable());
    }

    public function down(): void
    {
        Schema::table('pages', fn (Blueprint $table) => $table->dropColumn('display'));
    }
};
