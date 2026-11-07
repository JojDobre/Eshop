<?php

use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Controllers\AccountController;
use Modules\Customers\Http\Controllers\AuthController;
use Modules\Customers\Http\Controllers\PasswordResetController;
use Modules\Customers\Http\Middleware\AuthenticateCustomer;
use Modules\Customers\Http\Middleware\RedirectIfCustomer;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->prefix('ucet')->name('customer.')->group(function (): void {
    Route::middleware(RedirectIfCustomer::class)->group(function (): void {
        Route::get('/prihlasenie', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/prihlasenie', [AuthController::class, 'login'])->middleware('throttle:20,1');
        Route::get('/registracia', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/registracia', [AuthController::class, 'register'])->middleware('throttle:10,1');
        Route::get('/zabudnute-heslo', [PasswordResetController::class, 'showRequest'])->name('password.request');
        Route::post('/zabudnute-heslo', [PasswordResetController::class, 'sendLink'])->name('password.email')->middleware('throttle:6,1');
        Route::get('/nove-heslo/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
        Route::post('/nove-heslo', [PasswordResetController::class, 'reset'])->name('password.update')->middleware('throttle:10,1');
    });

    Route::middleware(AuthenticateCustomer::class)->group(function (): void {
        Route::get('/', [AccountController::class, 'orders'])->name('account');
        Route::get('/udaje', [AccountController::class, 'profile'])->name('profile');
        Route::put('/udaje', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::put('/heslo', [AccountController::class, 'updatePassword'])->name('profile.password');
        Route::delete('/', [AccountController::class, 'destroy'])->name('destroy');
        Route::post('/odhlasenie', [AuthController::class, 'logout'])->name('logout');
    });
});
