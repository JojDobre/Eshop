<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vlastné štítky produktov (napr. Vlajková loď, Bio, Domáce) s farbou.
        Schema::create('product_labels', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('color', 7)->default('#e8772a');
            $table->string('text_color', 7)->default('#ffffff');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_label_product', function (Blueprint $table) {
            $table->foreignId('product_label_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_label_id', 'product_id']);
        });

        // Vlastné stavy dostupnosti (Skladom, Na objednávku, Nedostupné…) namiesto počtu kusov na sklade.
        Schema::create('availability_statuses', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('color', 7)->default('#43a047');
            // Dá sa produkt v tomto stave kúpiť?
            $table->boolean('is_purchasable')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('availability_status_id')->nullable()->constrained()->nullOnDelete();
        });

        // Kde sa vlastné pole zobrazí na detaile produktu: v parametroch, pod stavom skladu, alebo nikde.
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->string('display', 16)->default('parameters');
        });
    }

    public function down(): void
    {
        Schema::table('custom_fields', fn (Blueprint $table) => $table->dropColumn('display'));
        Schema::table('products', fn (Blueprint $table) => $table->dropConstrainedForeignId('availability_status_id'));
        Schema::dropIfExists('availability_statuses');
        Schema::dropIfExists('product_label_product');
        Schema::dropIfExists('product_labels');
    }
};
