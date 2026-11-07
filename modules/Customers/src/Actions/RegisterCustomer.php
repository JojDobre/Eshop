<?php

namespace Modules\Customers\Actions;

use App\Core\Mail\EmailTemplates;
use App\Core\Mail\TemplateMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Customers\Models\Customer;
use Modules\Storefront\Pages\LegalPages;
use Throwable;

class RegisterCustomer
{
    /**
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function handle(array $data, string $locale): Customer
    {
        $customer = Customer::query()->create([
            'name' => trim($data['name']),
            'email' => $data['email'],
            'password' => $data['password'],
            'locale' => $locale,
            'terms_accepted_at' => now(),
            'legal_consents' => app(LegalPages::class)->consentRecords(),
        ]);

        if (app(EmailTemplates::class)->isEnabled('customers.welcome')) {
            try {
                Mail::to($customer->email, $customer->name)->send(new TemplateMail('customers.welcome', [
                    'customer_name' => $customer->name,
                    'account_url' => route('customer.account'),
                ], $locale, ['account_url']));
            } catch (Throwable $e) {
                Log::error('Welcome e-mail failed.', ['customer' => $customer->id, 'exception' => $e]);
            }
        }

        return $customer;
    }
}
