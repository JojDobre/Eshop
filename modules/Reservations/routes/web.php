<?php

use Illuminate\Support\Facades\Route;
use Modules\Reservations\Http\Controllers\ReservationController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/rezervacia', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/rezervacia/sluzba/{service}', [ReservationController::class, 'service'])->name('reservations.service')->whereNumber('service');
    Route::post('/rezervacia/sluzba/{service}', [ReservationController::class, 'book'])->name('reservations.book')->whereNumber('service')->middleware('throttle:10,1');
    Route::post('/rezervacia/produkt/{product}', [ReservationController::class, 'bookProduct'])->name('reservations.product')->whereNumber('product')->middleware('throttle:10,1');
    Route::get('/rezervacia/{token}', [ReservationController::class, 'show'])->name('reservations.show')->where('token', '[A-Za-z0-9]{40}');
    Route::post('/rezervacia/{token}/zrusit', [ReservationController::class, 'cancel'])->name('reservations.cancel')->where('token', '[A-Za-z0-9]{40}');
    Route::get('/rezervacia/{token}/kalendar.ics', [ReservationController::class, 'ics'])->name('reservations.ics')->where('token', '[A-Za-z0-9]{40}');
});

Route::get('/rezervacie/kalendar/{token}.ics', [ReservationController::class, 'feed'])->name('reservations.feed')->where('token', '[A-Za-z0-9]{40}');
