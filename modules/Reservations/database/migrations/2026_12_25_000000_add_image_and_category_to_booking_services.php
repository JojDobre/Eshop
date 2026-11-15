<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fotka a kategória služby (napr. Vlasy, Pleť) na stránke služieb a v šablónach.
        Schema::table('booking_services', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('description');
            $table->json('category')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('booking_services', function (Blueprint $table): void {
            $table->dropColumn(['image', 'category']);
        });
    }
};
