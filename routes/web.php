<?php

use App\Http\Controllers\CronController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ThemeAssetController;
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

// Webový cron pre hostingy bez plánovača úloh (adresa s tajným kľúčom je v Systém → Stav systému).
Route::get('/cron/{token}', CronController::class)
    ->name('cron.web')
    ->where('token', '[a-f0-9]{32}')
    ->middleware('throttle:6,1');

// Súbory šablón vzhľadu (bez kompilácie a symbolických odkazov, pozri theme_asset()).
Route::get('/theme-assets/{theme}/{path}', [ThemeAssetController::class, 'asset'])
    ->where(['theme' => '[a-z][a-z0-9-]*', 'path' => '[A-Za-z0-9_\-./]+'])->name('theme.asset');
Route::get('/theme-screenshot/{theme}', [ThemeAssetController::class, 'screenshot'])
    ->where('theme', '[a-z][a-z0-9-]*')->name('theme.screenshot');
