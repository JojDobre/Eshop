<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Číselné rady (čísla objednávok, neskôr faktúr) – bezpečné aj pri súbežných objednávkach.
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->string('key', 64)->primary();
            $table->unsignedBigInteger('value')->default(0);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            // Náhodný kľúč pre odkaz na objednávku bez prihlásenia.
            $table->string('token', 64)->unique();
            $table->string('status', 32)->index();
            $table->string('payment_status', 32)->index();
            $table->string('locale', 8);
            $table->char('currency', 3);

            $table->string('email');
            $table->string('phone', 40)->nullable();

            $table->string('billing_name');
            $table->string('billing_company')->nullable();
            $table->string('billing_company_id', 32)->nullable();
            $table->string('billing_tax_id', 32)->nullable();
            $table->string('billing_vat_id', 32)->nullable();
            $table->string('billing_street');
            $table->string('billing_city');
            $table->string('billing_zip', 16);
            $table->char('billing_country', 2);

            $table->string('shipping_name')->nullable();
            $table->string('shipping_street')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_zip', 16)->nullable();
            $table->char('shipping_country', 2)->nullable();

            // Doprava a platba: odkaz aj kópia údajov z času objednávky (nastavenia sa môžu neskôr zmeniť).
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shipping_method_name');
            $table->string('shipping_type', 32);
            $table->text('shipping_details')->nullable();
            $table->unsignedBigInteger('shipping_price');
            $table->foreignId('payment_method_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_method_name');
            $table->string('payment_type', 32);
            $table->text('payment_instructions')->nullable();
            $table->unsignedBigInteger('payment_fee');

            // Sumy v najmenších jednotkách meny, s DPH.
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total');

            $table->text('customer_note')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku', 64)->nullable();
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('unit_price');
            $table->unsignedBigInteger('total');
        });

        Schema::create('order_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // created, status, payment, note
            $table->string('type', 32);
            $table->json('data')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_history');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('number_sequences');
    }
};
