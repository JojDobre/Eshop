<?php

use App\Providers\AppServiceProvider;
use App\Providers\EshopServiceProvider;
use App\Providers\Filament\AdminPanelProvider;

return [
    EshopServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
];
