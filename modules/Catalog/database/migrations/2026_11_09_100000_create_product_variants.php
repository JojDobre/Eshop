<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Možnosti produktu (napr. Veľkosť, Farba) a ich hodnoty (S, M, L / červená, modrá).
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->json('value');
            // Farba pre zobrazenie ako farebný krúžok (#rrggbb), nepovinné.
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        // Variant = jedna kombinácia hodnôt (napr. M / červená) s vlastným skladom, SKU a obrázkami.
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 64)->nullable()->unique();
            $table->integer('stock_quantity')->default(0);
            $table->unsignedInteger('weight')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_option_value_id')->constrained()->cascadeOnDelete();

            $table->primary(['product_variant_id', 'product_option_value_id']);
        });

        // Cena variantu (bez záznamu = cena produktu).
        Schema::table('product_prices', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->cascadeOnDelete();
            $table->index(['product_id', 'currency']);
            $table->dropUnique(['product_id', 'currency']);
            $table->unique(['product_id', 'product_variant_id', 'currency']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', fn (Blueprint $table) => $table->dropConstrainedForeignId('product_variant_id'));
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'product_variant_id', 'currency']);
            $table->dropConstrainedForeignId('product_variant_id');
            $table->unique(['product_id', 'currency']);
        });
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
    }
};
