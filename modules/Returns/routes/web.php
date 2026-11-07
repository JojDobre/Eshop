<?php

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\ReturnRequestController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/objednavka/{token}/vratenie', [ReturnRequestController::class, 'create'])
        ->name('returns.create')->where('token', '[A-Za-z0-9]{32,64}');
    Route::post('/objednavka/{token}/vratenie', [ReturnRequestController::class, 'store'])
        ->name('returns.store')->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:10,1');
});
