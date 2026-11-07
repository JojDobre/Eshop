<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            // pickup = osobný odber, delivery = doručenie na adresu
            $table->string('type', 32);
            // Cena v najmenších jednotkách meny (s DPH).
            $table->unsignedBigInteger('price')->default(0);
            $table->char('currency', 3);
            // Doprava zadarmo od tejto sumy objednávky (null = nikdy).
            $table->unsignedBigInteger('free_from')->nullable();
            // Adresa a otváracie hodiny pri osobnom odbere.
            $table->json('pickup_details')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
