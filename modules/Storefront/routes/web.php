<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\CurrencyController;
use Modules\Storefront\Http\Controllers\HomeController;
use Modules\Storefront\Http\Controllers\SeoController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/', HomeController::class)->name('storefront.home');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('storefront.sitemap');
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('storefront.robots');
    Route::post('/mena', CurrencyController::class)->name('storefront.currency')->middleware('throttle:30,1');
});
