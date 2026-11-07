<?php

namespace Modules\Stripe;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Orders\Payments\PaymentGateways;

class StripeServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'stripe';
    }

    public function register(): void
    {
        $this->app->bind(StripeApi::class, StripeClientApi::class);
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(PaymentGateways::class)->register($this->app->make(StripeGateway::class));
    }
}
