<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            // Kód sa ukladá veľkými písmenami, zákazník ho môže zadať ľubovoľne.
            $table->string('code', 64)->unique();
            $table->string('description')->nullable();
            // percent, fixed (pevná suma), free_shipping
            $table->string('type', 32);
            // Percentá (napr. 10) alebo suma v najmenších jednotkách meny.
            $table->unsignedBigInteger('value')->default(0);
            $table->char('currency', 3);
            $table->unsignedBigInteger('min_order_amount')->nullable();
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('coupon_code', 64)->nullable()->after('payment_fee');
            // Zľava z tovaru (s DPH, v najmenších jednotkách meny).
            $table->unsignedBigInteger('discount')->default(0)->after('coupon_code');
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['coupon_code', 'discount']));
        Schema::dropIfExists('coupons');
    }
};
