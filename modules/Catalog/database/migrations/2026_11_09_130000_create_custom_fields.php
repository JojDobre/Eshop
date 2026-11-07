<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vlastné polia (parametre) – obchodník si sám definuje, čo pri produkte eviduje.
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            // Objekt, ku ktorému pole patrí (zatiaľ product, neskôr aj zákazník, objednávka…).
            $table->string('entity', 32)->default('product');
            $table->string('key', 64);
            $table->json('name');
            $table->json('help')->nullable();
            $table->string('type', 32);
            $table->string('unit', 32)->nullable();
            // Možnosti pre výber: [{"key": "bavlna", "label": {"sk": "Bavlna"}}]
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['entity', 'key']);
        });

        // Hodnoty v stĺpcoch podľa typu (s indexmi), aby boli filtre rýchle aj pri tisícoch produktov.
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 32);
            $table->unsignedBigInteger('entity_id');
            $table->string('value_string')->nullable();
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 6)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(['custom_field_id', 'entity_type', 'entity_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['custom_field_id', 'value_string']);
            $table->index(['custom_field_id', 'value_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
        Schema::dropIfExists('custom_fields');
    }
};
