<?php

namespace Modules\Customers\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Customers\Models\Customer;

/**
 * Zrušenie účtu: zmažú sa osobné údaje účtu. Objednávky ostanú (účtovníctvo, záruky),
 * iba sa odpoja od účtu.
 */
class DeleteCustomer
{
    public function handle(Customer $customer): void
    {
        DB::transaction(function () use ($customer): void {
            DB::table('customer_password_reset_tokens')->where('email', $customer->email)->delete();
            $customer->orders()->update(['customer_id' => null]);
            $customer->delete();
        });
    }
}
