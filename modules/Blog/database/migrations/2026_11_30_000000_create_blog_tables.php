<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Blog: články, kategórie, štítky, komentáre, verzie a prepojenie s produktmi a kategóriami katalógu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('description')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('blog_tags', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->timestamps();
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('excerpt')->nullable();
            $table->json('blocks')->nullable();
            $table->string('cover_image')->nullable();
            $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            // draft = koncept, published = zverejnený (od published_at, budúci dátum = naplánované)
            $table->string('status', 16)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->boolean('comments_enabled')->default(true);
            $table->string('preview_token', 64)->unique();
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
        });

        Schema::create('blog_post_tag', function (Blueprint $table) {
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['blog_post_id', 'blog_tag_id']);
        });

        // Odporúčané produkty v článku a súvisiace články pri produkte (to isté prepojenie z oboch strán).
        Schema::create('blog_post_product', function (Blueprint $table) {
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['blog_post_id', 'product_id']);
        });

        Schema::create('blog_post_catalog_category', function (Blueprint $table) {
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['blog_post_id', 'category_id']);
        });

        Schema::create('blog_post_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('name');
            $table->json('excerpt')->nullable();
            $table->json('blocks')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('blog_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('email');
            $table->text('content');
            $table->boolean('is_approved')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['blog_post_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        foreach (['blog_comments', 'blog_post_versions', 'blog_post_catalog_category', 'blog_post_product', 'blog_post_tag', 'blog_posts', 'blog_tags', 'blog_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
