<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Nastavenia šablón vzhľadu (každá šablóna má vlastné) a položky ponúk (menu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('theme_settings', function (Blueprint $table) {
            $table->id();
            $table->string('theme', 64)->unique();
            $table->json('values');
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            // Miesto v šablóne, napr. „header“ alebo „footer“ (definuje theme.json).
            $table->string('location', 64);
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->json('label')->nullable();
            // Typ odkazu: url, page, category, product (registrujú moduly).
            $table->string('type', 32)->default('url');
            $table->string('target')->nullable();
            $table->boolean('new_window')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['location', 'parent_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('theme_settings');
    }
};
