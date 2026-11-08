<?php

use Illuminate\Support\Facades\Route;
use Modules\Marketing\Http\Controllers\AbandonedCartController;
use Modules\Marketing\Http\Controllers\FeedController;
use Modules\Marketing\Http\Controllers\NewsletterController;
use Modules\Storefront\Http\Middleware\SetStorefrontLocale;

Route::middleware(SetStorefrontLocale::class)->group(function (): void {
    Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:5,1');
    Route::get('/newsletter/potvrdit/{token}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm')->where('token', '[A-Za-z0-9]{48}');
    Route::get('/newsletter/odhlasit/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe')->where('token', '[A-Za-z0-9]{48}');
    Route::post('/pokladna/email-kosika', [AbandonedCartController::class, 'email'])->name('marketing.cart.email')->middleware('throttle:20,1');
    Route::get('/kosik/obnovit/{token}', [AbandonedCartController::class, 'restore'])->name('marketing.cart.restore')->whereUuid('token');
});

// XML feedy pre porovnávače cien a reklamné katalógy (bez jazykovej predpony, s parametrom ?lang=).
Route::get('/feed/{type}.xml', FeedController::class)->name('marketing.feed')->whereIn('type', ['heureka', 'google', 'facebook']);
