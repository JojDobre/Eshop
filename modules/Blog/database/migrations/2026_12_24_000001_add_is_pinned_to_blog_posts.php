<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Pripnutý článok sa zobrazí ako banner na vrchu zoznamu článkov.
    public function up(): void
    {
        Schema::table('blog_posts', fn (Blueprint $table) => $table->boolean('is_pinned')->default(false)->after('comments_enabled'));
    }

    public function down(): void
    {
        Schema::table('blog_posts', fn (Blueprint $table) => $table->dropColumn('is_pinned'));
    }
};
