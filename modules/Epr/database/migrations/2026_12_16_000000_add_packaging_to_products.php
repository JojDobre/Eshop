<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hmotnosť obalu jedného kusu v gramoch podľa materiálu: {"paper": 12, "plastic": 3}.
        Schema::table('products', fn (Blueprint $table) => $table->json('packaging')->nullable());
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('packaging'));
    }
};
