<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Archív verzií právnych stránok (obchodné podmienky, ochrana osobných údajov).
        // Verzie sa nemenia ani nemažú – slúžia ako doklad, s čím zákazník súhlasil.
        Schema::create('page_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('name');
            $table->json('content')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->unique(['page_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_versions');
    }
};
