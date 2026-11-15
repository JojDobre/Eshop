<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uložené sekcie: bloky, ktoré sa vkladajú na viac miest (úprava sa prejaví všade).
        Schema::create('saved_blocks', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->json('blocks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_blocks');
    }
};
