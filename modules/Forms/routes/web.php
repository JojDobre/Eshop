<?php

use Illuminate\Support\Facades\Route;
use Modules\Forms\Http\Controllers\FormController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::post('/formular/{form}', [FormController::class, 'submit'])->name('forms.submit')->middleware('throttle:5,1');
});
