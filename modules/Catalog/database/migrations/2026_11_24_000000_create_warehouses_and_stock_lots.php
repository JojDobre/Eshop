<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sklady a skladové zásoby po skladoch a šaržiach.
 *
 * Celkový stav ostáva v products.stock_quantity / product_variants.stock_quantity (rýchle čítanie),
 * stock_lots ho rozpisuje podľa skladu a šarže. Rozpis sa používa iba so zapnutými funkciami.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('code', 32)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        DB::table('warehouses')->insert([
            'name' => json_encode(['sk' => 'Hlavný sklad', 'cs' => 'Hlavní sklad', 'en' => 'Main warehouse'], JSON_UNESCAPED_UNICODE),
            'is_active' => true,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('stock_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->restrictOnDelete();
            $table->string('batch_number', 64)->nullable();
            $table->date('produced_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->index(['product_id', 'product_variant_id']);
            $table->index('expires_at');
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_lot_id')->nullable()->constrained()->nullOnDelete();
            // Číslo šarže ostane v histórii aj po zmazaní šarže (dohľadateľnosť).
            $table->string('batch_number', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stock_lot_id');
            $table->dropConstrainedForeignId('warehouse_id');
            $table->dropColumn('batch_number');
        });
        Schema::dropIfExists('stock_lots');
        Schema::dropIfExists('warehouses');
    }
};
