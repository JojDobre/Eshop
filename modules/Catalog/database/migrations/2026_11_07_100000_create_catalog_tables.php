<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            // Preložiteľné stĺpce (JSON podľa jazyka, napr. {"sk": "Káva"}).
            $table->json('name');
            $table->json('slug');
            $table->json('description')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'position']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->string('sku', 64)->nullable()->unique();
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->json('images')->nullable();
            $table->boolean('track_stock')->default(true);
            $table->integer('stock_quantity')->default(0);
            // Hmotnosť v gramoch (pre výpočet dopravy).
            $table->unsignedInteger('weight')->nullable();
            $table->boolean('is_visible')->default(true);
            // Normalizovaný text na vyhľadávanie (malé písmená, bez diakritiky).
            $table->text('search_text')->nullable();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->index(['is_visible', 'created_at']);
        });

        // Ceny podľa meny v najmenších jednotkách (centy, haliere). Cena je s DPH.
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->timestamps();

            $table->unique(['product_id', 'currency']);
            $table->index(['currency', 'amount']);
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);

            $table->primary(['category_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_product');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
