<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vlastné spôsoby dopravy: krajiny doručenia, dodacia lehota a rozsah sumy objednávky.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            // Kódy krajín, kam sa doručuje (prázdne = všetky).
            $table->json('countries')->nullable();
            // Dodacia lehota pre zákazníka v každom jazyku, napr. „do 24 hodín“.
            $table->json('delivery_time')->nullable();
            // Ponúknuť iba pre objednávky od – do (v mene spôsobu dopravy).
            $table->unsignedBigInteger('min_order')->nullable();
            $table->unsignedBigInteger('max_order')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipping_methods', fn (Blueprint $table) => $table->dropColumn(['countries', 'delivery_time', 'min_order', 'max_order']));
    }
};
