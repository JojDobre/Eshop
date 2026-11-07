<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sadzby DPH z času objednávky (pre doklady a rozpis DPH).
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(0)->after('total');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('vat_payer')->default(true)->after('currency');
            $table->decimal('shipping_tax_rate', 5, 2)->default(0)->after('shipping_price');
            $table->decimal('payment_tax_rate', 5, 2)->default(0)->after('payment_fee');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('tax_rate'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['vat_payer', 'shipping_tax_rate', 'payment_tax_rate']));
    }
};
