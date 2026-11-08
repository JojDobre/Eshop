<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kuriéri: napojenie spôsobu dopravy na kuriéra, výdajné miesta a obmedzenia dopravy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            // Kuriér (packeta, gls, dpd, manual…) a jeho nastavenia pre tento spôsob dopravy.
            $table->string('carrier', 32)->nullable();
            $table->json('carrier_options')->nullable();
            // Skutočné náklady na dopravu (pre prehľad ziskovosti), v mene spôsobu dopravy.
            $table->unsignedBigInteger('cost')->nullable();
            // Vlastný rozvoz: iba pre PSČ začínajúce týmito predponami.
            $table->json('zip_prefixes')->nullable();
            // Doprava sa neponúkne, ak košík obsahuje produkty týchto typov alebo kategórií.
            $table->json('excluded_product_type_ids')->nullable();
            $table->json('excluded_category_ids')->nullable();
        });

        Schema::create('pickup_points', function (Blueprint $table) {
            $table->id();
            $table->string('carrier', 32);
            $table->string('code', 64);
            $table->string('name');
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('zip', 16)->nullable();
            $table->char('country', 2);
            // parcelshop, locker…
            $table->string('type', 32)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('opening_hours')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('synced_at')->nullable();

            $table->unique(['carrier', 'code']);
            $table->index(['carrier', 'country', 'city']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_points');
        Schema::table('shipping_methods', fn (Blueprint $table) => $table->dropColumn(['carrier', 'carrier_options', 'cost', 'zip_prefixes', 'excluded_product_type_ids', 'excluded_category_ids']));
    }
};
