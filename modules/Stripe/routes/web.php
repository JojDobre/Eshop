<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;
use Modules\Stripe\Http\Controllers\StripeReturnController;
use Modules\Stripe\Http\Controllers\StripeWebhookController;

Route::get('/stripe/navrat/{token}', [StripeReturnController::class, 'success'])
    ->name('stripe.return')->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:30,1');
Route::get('/stripe/zrusene/{token}', [StripeReturnController::class, 'cancel'])
    ->name('stripe.cancel')->where('token', '[A-Za-z0-9]{32,64}');

// Stripe posiela upozornenia bez CSRF tokenu – overuje sa podpisom.
Route::post('/stripe/webhook', StripeWebhookController::class)
    ->name('stripe.webhook')->withoutMiddleware(PreventRequestForgery::class)->middleware('throttle:120,1');
