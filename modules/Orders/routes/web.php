<?php

use Illuminate\Support\Facades\Route;
use Modules\Orders\Http\Controllers\CheckoutController;
use Modules\Orders\Http\Controllers\CouponController;
use Modules\Orders\Http\Controllers\DownloadController;
use Modules\Orders\Http\Controllers\OrderController;
use Modules\Orders\Http\Controllers\PaymentController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/pokladna', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/pokladna', [CheckoutController::class, 'store'])->name('checkout.store')->middleware('throttle:20,1');
    Route::post('/kosik/kupon', [CouponController::class, 'apply'])->name('coupon.apply')->middleware('throttle:20,1');
    Route::delete('/kosik/kupon', [CouponController::class, 'remove'])->name('coupon.remove');
    Route::get('/objednavka/{token}/zaplatit', PaymentController::class)->name('orders.pay')->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:20,1');
    Route::get('/objednavka/{token}', OrderController::class)->name('orders.show')->where('token', '[A-Za-z0-9]{32,64}');
    Route::get('/stiahnut/{token}', DownloadController::class)->name('downloads.show')->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:30,1');
});
