<?php

use Illuminate\Support\Facades\Route;
use Modules\Pages\Http\Controllers\PageController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::get('/stranka/{slug}', PageController::class)->name('pages.show');
});
