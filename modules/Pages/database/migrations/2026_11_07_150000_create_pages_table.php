<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('content')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            // Účel stránky pre odkazy z obchodu (terms, privacy, …). Nepovinné.
            $table->string('role', 32)->nullable()->unique();
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
