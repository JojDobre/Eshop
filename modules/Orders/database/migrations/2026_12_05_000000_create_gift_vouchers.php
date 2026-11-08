<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Darčekové poukážky (viacúčelové – v pokladni sa správajú ako platba, nie ako zľava).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gift_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->unsignedBigInteger('initial_amount');
            $table->unsignedBigInteger('balance');
            $table->char('currency', 3);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            // Objednávka, v ktorej bola poukážka kúpená (null = vydaná ručne).
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_email')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        // Čerpanie (kladná suma) a vrátenie pri zrušení objednávky (záporná suma).
        Schema::create('gift_voucher_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gift_voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount');
            $table->timestamp('created_at')->nullable();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('voucher_code', 32)->nullable();
            // Časť sumy uhradená poukážkou (celková suma objednávky sa nemení).
            $table->unsignedBigInteger('voucher_amount')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['voucher_code', 'voucher_amount']));
        Schema::dropIfExists('gift_voucher_usages');
        Schema::dropIfExists('gift_vouchers');
    }
};
