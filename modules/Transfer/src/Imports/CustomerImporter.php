<?php

namespace Modules\Transfer\Imports;

use App\Models\CustomerGroup;
use Illuminate\Support\Str;
use Modules\Customers\Models\Customer;

/**
 * Zákazníci podľa e-mailu. Nový zákazník dostane náhodné heslo (prihlási sa cez „Zabudnuté heslo“).
 */
class CustomerImporter implements Importer
{
    public function key(): string
    {
        return 'customers';
    }

    public function label(): string
    {
        return __('transfer::admin.export.entities.customers');
    }

    public function keyField(): string
    {
        return 'email';
    }

    public function fields(): array
    {
        return collect(['email', 'name', 'phone', 'group', 'company', 'company_id', 'vat_id', 'street', 'city', 'zip', 'country'])
            ->mapWithKeys(fn ($key) => [$key => __('transfer::admin.columns.'.$key)])->all();
    }

    public function validate(array $data): array
    {
        $errors = [];

        if (! filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('transfer::admin.import.errors.email');
        }

        if (blank($data['name'] ?? null) && ! Customer::query()->where('email', Str::lower($data['email'] ?? ''))->exists()) {
            $errors[] = __('transfer::admin.import.errors.name');
        }

        if (filled($data['country'] ?? null) && ! preg_match('/^[A-Za-z]{2}$/', $data['country'])) {
            $errors[] = __('transfer::admin.import.errors.country');
        }

        return $errors;
    }

    public function import(array $data): bool
    {
        $email = Str::lower(trim($data['email']));
        $customer = Customer::query()->where('email', $email)->first();
        $created = $customer === null;
        $customer ??= new Customer(['email' => $email, 'password' => Str::random(40)]);

        $address = array_filter([
            'name' => $data['name'] ?? null, 'company' => $data['company'] ?? null, 'company_id' => $data['company_id'] ?? null,
            'vat_id' => $data['vat_id'] ?? null, 'street' => $data['street'] ?? null, 'city' => $data['city'] ?? null,
            'zip' => $data['zip'] ?? null, 'country' => filled($data['country'] ?? null) ? strtoupper($data['country']) : null,
        ], fn ($value) => filled($value));

        $customer->fill(array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'customer_group_id' => filled($data['group'] ?? null) ? CustomerGroup::query()->where('code', $data['group'])->value('id') : null,
        ], fn ($value) => filled($value)));

        if ($address !== []) {
            $customer->billing_address = [...(array) $customer->billing_address, ...$address];
        }

        $customer->save();

        return $created;
    }
}
