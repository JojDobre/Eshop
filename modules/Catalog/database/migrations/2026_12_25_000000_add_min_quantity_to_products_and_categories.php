<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Minimálne množstvo v objednávke: pri produkte (jeho kusy spolu) a pri kategórii (kusy všetkých jej produktov spolu).
    public function up(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->unsignedInteger('min_quantity')->nullable());
        Schema::table('categories', fn (Blueprint $table) => $table->unsignedInteger('min_quantity')->nullable());
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('min_quantity'));
        Schema::table('categories', fn (Blueprint $table) => $table->dropColumn('min_quantity'));
    }
};
