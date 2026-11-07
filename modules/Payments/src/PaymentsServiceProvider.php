<?php

namespace Modules\Payments;

use App\Core\Content\TranslatableModels;
use App\Core\Events\EshopInstalled;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Payments\Listeners\CreateDefaultPaymentMethods;
use Modules\Payments\Models\PaymentMethod;

class PaymentsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'payments';
    }

    public function boot(): void
    {
        parent::boot();

        // Preložiteľný obsah (hromadný preklad).
        $this->app->make(TranslatableModels::class)->register(PaymentMethod::class, fn () => __('payments::admin.plural'));

        Event::listen(EshopInstalled::class, CreateDefaultPaymentMethods::class);
    }
}
