<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Textové pole môže mať hodnotu v každom jazyku (uloží sa do value_json: {"sk": "…", "en": "…"}).
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->boolean('is_translatable')->default(false)->after('is_visible');
        });
    }

    public function down(): void
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropColumn('is_translatable');
        });
    }
};
