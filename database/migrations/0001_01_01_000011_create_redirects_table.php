<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Presmerovania starých adries (301), napr. po zmene adresy produktu alebo prechode z iného e-shopu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            // Cesta bez domény, napr. „/produkt/stara-kava“ (s otáznikom aj parametre).
            $table->string('from_path', 500)->unique();
            $table->string('to_url', 1000);
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_automatic')->default(false);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redirects');
    }
};
