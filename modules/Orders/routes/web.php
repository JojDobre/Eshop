<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\CheckoutController;
use Modules\Orders\Http\Controllers\OrderController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/pokladna', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/pokladna', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:20,1');
    Route::get('/objednavka/{token}', OrderController::class)->name('orders.show')->where('token', '[A-Za-z0-9]{32,64}');
});
