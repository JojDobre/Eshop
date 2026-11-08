<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vlastné ceny pre zákaznícke skupiny (zadané rovnako ako bežná cena – s DPH alebo bez podľa nastavení).
        Schema::create('product_group_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_group_id')->constrained()->cascadeOnDelete();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->timestamps();
            $table->unique(['product_id', 'product_variant_id', 'customer_group_id', 'currency'], 'product_group_prices_unique');
        });

        // Množstevné zľavy: od daného počtu kusov v košíku zľava v % (pre všetkých alebo pre skupinu).
        Schema::create('product_quantity_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_group_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('min_quantity');
            $table->decimal('discount_percent', 5, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_quantity_discounts');
        Schema::dropIfExists('product_group_prices');
    }
};
