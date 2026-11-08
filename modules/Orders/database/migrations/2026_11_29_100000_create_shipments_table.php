<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zásielky objednávok u kuriérov (štítky, sledovanie) a zvolené výdajné miesto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Výdajné miesto: {code, name, street, city, zip, country, carrier_code?}
            $table->json('pickup_point')->nullable();
            // Skutočné náklady na dopravu v mene objednávky (pre prehľad ziskovosti).
            $table->unsignedBigInteger('shipping_cost')->nullable();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('carrier', 32);
            // Identifikátor zásielky u kuriéra (napr. ID zásielky Packeta, ParcelId GLS).
            $table->string('carrier_reference', 64)->nullable();
            $table->string('tracking_number', 64)->nullable();
            $table->string('label_path')->nullable();
            // created, in_transit, ready_for_pickup, delivered, returned, cancelled
            $table->string('status', 32)->default('created');
            $table->string('status_text')->nullable();
            $table->unsignedBigInteger('cod_amount')->nullable();
            $table->string('cod_currency', 3)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_checked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['pickup_point', 'shipping_cost']));
    }
};
