<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\CartController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->controller(CartController::class)->group(function (): void {
    Route::get('/kosik', 'show')->name('cart.show');
    Route::post('/kosik', 'add')->name('cart.add')->middleware('throttle:60,1');
    Route::patch('/kosik/{item}', 'update')->name('cart.update')->whereNumber('item');
    Route::delete('/kosik/{item}', 'remove')->name('cart.remove')->whereNumber('item');
});
