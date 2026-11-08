<?php

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\BlogController;
use Modules\Blog\Http\Controllers\CommentController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
    Route::get('/blog/rss.xml', [BlogController::class, 'rss'])->name('blog.rss');
    Route::get('/blog/kategoria/{slug}', [BlogController::class, 'category'])->name('blog.category');
    Route::get('/blog/stitok/{slug}', [BlogController::class, 'tag'])->name('blog.tag');
    Route::get('/blog/nahlad/{token}', [BlogController::class, 'preview'])->name('blog.preview')->where('token', '[A-Za-z0-9]{40}');
    Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
    Route::post('/blog/{slug}/komentar', CommentController::class)->name('blog.comment')->middleware('throttle:5,1');
});
