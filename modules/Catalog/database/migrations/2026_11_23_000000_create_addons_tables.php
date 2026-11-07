<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Doplnkové voľby k produktu s príplatkom (darčekové balenie, gravírovanie…).
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            // Príplatok s DPH v najmenších jednotkách meny; v iných menách sa prepočíta kurzom.
            $table->unsignedBigInteger('price')->default(0);
            $table->char('currency', 3);
            // Zákazník zadá text (napr. text gravírovania).
            $table->boolean('needs_text')->default(false);
            $table->json('text_label')->nullable();
            $table->unsignedSmallInteger('text_max_length')->default(50);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('addon_product', function (Blueprint $table) {
            $table->foreignId('addon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['addon_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addon_product');
        Schema::dropIfExists('addons');
    }
};
