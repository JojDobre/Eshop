<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            // Objednávka, z ktorej je nákup overený (null = neoverené hodnotenie).
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 80);
            $table->string('email')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 120)->nullable();
            $table->text('body')->nullable();
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->string('status', 16)->default('pending');
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['product_id', 'status']);
        });

        // Súhrn pre výpis produktov (aktualizuje sa pri schválení a zmazaní hodnotenia).
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('reviews_count')->default(0);
            $table->decimal('rating_average', 3, 2)->nullable();
        });

        // Žiadosť o hodnotenie: kedy poslať (po dokončení objednávky) a kedy bola poslaná.
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('review_request_due_at')->nullable();
            $table->timestamp('review_requested_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['review_request_due_at', 'review_requested_at']));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['reviews_count', 'rating_average']));
        Schema::dropIfExists('product_reviews');
    }
};
