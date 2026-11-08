<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bloky nad zoznamom produktov kategórie (v každom jazyku).
        Schema::table('categories', fn (Blueprint $table) => $table->json('blocks')->nullable());
    }

    public function down(): void
    {
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('blocks'));
    }
};
