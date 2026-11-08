<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloky úvodnej stránky a oblastí šablóny (bloky stránok a kategórií sú pri nich).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('block_layouts', function (Blueprint $table) {
            $table->id();
            // „home“ alebo „area:{názov oblasti}“.
            $table->string('key', 96)->unique();
            // Bloky v každom jazyku: {"sk": [{"type": "...", "data": {...}}], ...}
            $table->json('blocks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('block_layouts');
    }
};
