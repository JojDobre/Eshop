<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Http\Controllers\CategoryController;
use Modules\Catalog\Http\Controllers\ComparisonController;
use Modules\Catalog\Http\Controllers\ProductController;
use Modules\Catalog\Http\Controllers\SearchController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/kategoria/{slug}', CategoryController::class)->name('catalog.category');
    Route::get('/produkt/{slug}', ProductController::class)->name('catalog.product');
    Route::get('/hladat', SearchController::class)->name('catalog.search');
    Route::get('/porovnanie', [ComparisonController::class, 'show'])->name('compare.show');
    Route::post('/porovnanie', [ComparisonController::class, 'add'])->name('compare.add')->middleware('throttle:60,1');
    Route::delete('/porovnanie/{product}', [ComparisonController::class, 'remove'])->name('compare.remove')->whereNumber('product');
    Route::delete('/porovnanie', [ComparisonController::class, 'clear'])->name('compare.clear');
});
