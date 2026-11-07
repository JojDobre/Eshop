<?php

namespace Modules\Payments;

use App\Core\Events\EshopInstalled;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Payments\Listeners\CreateDefaultPaymentMethods;

class PaymentsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'payments';
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(EshopInstalled::class, CreateDefaultPaymentMethods::class);
    }
}
