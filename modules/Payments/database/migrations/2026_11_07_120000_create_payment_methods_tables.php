<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            // bank_transfer, cash_on_delivery, cash (neskôr aj platobné brány)
            $table->string('type', 32);
            // Poplatok v najmenších jednotkách meny (s DPH).
            $table->unsignedBigInteger('fee')->default(0);
            $table->char('currency', 3);
            // Pokyny pre zákazníka po objednávke (napr. kedy platiť).
            $table->json('instructions')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // S ktorými spôsobmi dopravy sa platba dá použiť (žiadny záznam = so všetkými).
        Schema::create('payment_method_shipping_method', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_method_id')->constrained()->cascadeOnDelete();

            $table->primary(['payment_method_id', 'shipping_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_method_shipping_method');
        Schema::dropIfExists('payment_methods');
    }
};
