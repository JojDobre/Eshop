<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platobné relácie Stripe Checkout k objednávkam (každý pokus o platbu = jedna relácia).
        Schema::create('stripe_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->unique();
            $table->string('payment_intent')->nullable();
            $table->string('status', 32)->default('open');
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3);
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_payments');
    }
};
