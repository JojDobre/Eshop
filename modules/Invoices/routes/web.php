<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Http\Controllers\InvoiceDownloadController;

Route::get('/objednavka/{token}/doklad/{number}', [InvoiceDownloadController::class, 'customer'])
    ->name('invoices.customer')->where('token', '[A-Za-z0-9]{32,64}')->middleware('throttle:30,1');

Route::get('/admin/doklady/{invoice}/pdf', [InvoiceDownloadController::class, 'admin'])
    ->name('invoices.admin');
