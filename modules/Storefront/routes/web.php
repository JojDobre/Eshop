<?php

use Illuminate\Support\Facades\Route;
use Modules\Storefront\Http\Controllers\HomeController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/', HomeController::class)->name('storefront.home');
});
