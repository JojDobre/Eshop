<?php

namespace Modules\Customers;

use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Customers\Listeners\LinkOrderToCustomer;
use Modules\Customers\Models\Customer;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Models\Order;

class CustomersServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'customers';
    }

    public function register(): void
    {
        // Samostatné prihlasovanie zákazníkov (oddelené od administrácie).
        config([
            'auth.guards.customer' => ['driver' => 'session', 'provider' => 'customers'],
            'auth.providers.customers' => ['driver' => 'eloquent', 'model' => Customer::class],
            'auth.passwords.customers' => [
                'provider' => 'customers',
                'table' => 'customer_password_reset_tokens',
                'expire' => 60,
                'throttle' => 60,
            ],
        ]);

        Order::resolveRelationUsing('customer', fn (Order $order) => $order->belongsTo(Customer::class));
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(OrderPlaced::class, LinkOrderToCustomer::class);

        // Prihlásený zákazník v šablóne (hlavička) a predvyplnenie pokladne.
        View::composer('theme::*', function (\Illuminate\View\View $view): void {
            $view->with('customer', auth('customer')->user());
        });

        View::composer('theme::templates.checkout', function (\Illuminate\View\View $view): void {
            $customer = auth('customer')->user();
            $view->with('prefill', $customer instanceof Customer ? CheckoutPrefill::for($customer) : []);
        });

        $this->registerEmailTemplates();
    }

    private function registerEmailTemplates(): void
    {
        $templates = $this->app->make(EmailTemplates::class);
        $sample = ['customer_name' => 'Jana Nováková', 'account_url' => url('/ucet'), 'reset_url' => url('/ucet')];

        $templates->register(new EmailTemplateDefinition('customers.welcome', 'customers::mail_templates.welcome',
            ['customer_name', 'account_url', 'shop_name'], canBeDisabled: true, sample: $sample));
        $templates->register(new EmailTemplateDefinition('customers.password_reset', 'customers::mail_templates.password_reset',
            ['customer_name', 'reset_url', 'shop_name'], sample: $sample));
    }
}
