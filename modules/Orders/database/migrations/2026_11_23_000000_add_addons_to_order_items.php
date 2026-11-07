<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Doplnkové voľby v čase nákupu: [{"name": "Darčekové balenie", "price": 290, "text": null}] (cena je zahrnutá v jednotkovej cene).
            $table->json('addons')->nullable()->after('variant_name');
            // Odkiaľ sa tovar vydal (sklad, šarža, zložky balíčka) – na vrátenie a dohľadanie.
            $table->json('allocations')->nullable()->after('addons');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['addons', 'allocations']);
        });
    }
};
