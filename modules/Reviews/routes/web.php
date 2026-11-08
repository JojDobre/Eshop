<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\ReviewController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::post('/produkt/{product}/hodnotenie', [ReviewController::class, 'store'])->name('reviews.store')->middleware('throttle:5,1');
    Route::get('/hodnotenie/{order}', [ReviewController::class, 'order'])->name('reviews.order')->middleware('signed');
    Route::post('/hodnotenie/{order}', [ReviewController::class, 'storeForOrder'])->name('reviews.order.store')->middleware(['signed', 'throttle:20,1']);
});
