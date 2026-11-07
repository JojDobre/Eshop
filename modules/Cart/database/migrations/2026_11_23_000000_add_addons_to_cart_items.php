<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Zvolené doplnkové voľby: {"3": null, "5": "Pre Janu"}. Rovnaký produkt s inými voľbami je samostatná položka.
        Schema::table('cart_items', function (Blueprint $table) {
            $table->json('addons')->nullable()->after('quantity');
            $table->string('addons_hash', 40)->nullable()->after('addons');
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropColumn(['addons', 'addons_hash']);
        });
    }
};
