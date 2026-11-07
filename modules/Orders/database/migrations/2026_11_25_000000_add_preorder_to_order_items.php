<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Aspoň časť položky bola v čase objednávky predobjednávkou (tovar ešte nebol na sklade).
            $table->boolean('is_preorder')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('is_preorder'));
    }
};
