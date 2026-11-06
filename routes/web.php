<?php

use App\Http\Controllers\InstallController;
use App\Http\Middleware\SetInstallerLocale;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webový inštalátor
|--------------------------------------------------------------------------
|
| Dostupný iba pred inštaláciou (pozri middleware EnsureInstalled).
| Routy obchodu a administrácie definujú moduly a Filament.
|
*/

Route::prefix('install')
    ->name('install.')
    ->middleware(SetInstallerLocale::class)
    ->controller(InstallController::class)
    ->group(function (): void {
        Route::get('/', 'welcome')->name('welcome');
        Route::post('/locale', 'setLocale')->name('locale');
        Route::get('/requirements', 'requirements')->name('requirements');
        Route::get('/database', 'database')->name('database');
        Route::post('/database', 'saveDatabase')->name('database.save');
        Route::get('/shop', 'shop')->name('shop');
        Route::post('/shop', 'install')->name('run')->middleware('throttle:10,1');
        Route::get('/done', 'done')->name('done');
    });
