<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zákaznícke skupiny (napr. Maloobchod, Veľkoobchod, VIP) – vlastné ceny, zľavy a zobrazenie cien bez DPH.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_groups', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('code', 32)->unique();
            // Všeobecná zľava skupiny v % (ak produkt nemá vlastnú cenu pre skupinu).
            $table->decimal('discount_percent', 5, 2)->default(0);
            // B2B: ceny sa zákazníkom skupiny zobrazujú hlavne bez DPH.
            $table->boolean('prices_without_vat')->default(false);
            // Skupina pre návštevníkov a zákazníkov bez priradenej skupiny.
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_groups');
    }
};
