<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\SearchController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/kategoria/{slug}', CategoryController::class)->name('catalog.category');
    Route::get('/produkt/{slug}', ProductController::class)->name('catalog.product');
    Route::get('/hladat', SearchController::class)->name('catalog.search');
});
