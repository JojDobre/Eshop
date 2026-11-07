<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Odkazy na stiahnutie digitálnych produktov z objednávky.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('download_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_file_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('token', 64)->unique();
            $table->unsignedInteger('downloads')->default(0);
            $table->unsignedInteger('max_downloads')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamps();

            $table->unique(['order_item_id', 'product_file_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('download_links');
    }
};
