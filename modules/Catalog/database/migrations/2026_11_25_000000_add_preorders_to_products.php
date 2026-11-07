<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Predobjednávky: produkt sa dá objednať aj bez tovaru na sklade (stav skladu ide do mínusu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('preorder_enabled')->default(false);
            $table->date('preorder_available_at')->nullable();
            // Najviac predobjednaných kusov (null = bez obmedzenia).
            $table->unsignedInteger('preorder_limit')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['preorder_enabled', 'preorder_available_at', 'preorder_limit']));
    }
};
