<?php

namespace Modules\Transfer\Exports;

use App\Core\Support\Currencies;
use App\Core\Support\Price;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\Models\Product;
use Modules\Customers\Models\Customer;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;

/**
 * Exportovateľné entity. Ďalšie moduly (napr. rezervácie) pridajú svoje cez register().
 */
final class Exports
{
    /** @var array<string, ExportDefinition> */
    private array $definitions = [];

    public function register(ExportDefinition $definition): void
    {
        $this->definitions[$definition->key] = $definition;
    }

    public function find(string $key): ?ExportDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /**
     * @return array<string, ExportDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    public static function money(?int $amount, string $currency): ?string
    {
        return $amount === null ? null : Price::toDecimal($amount, $currency);
    }

    public function registerDefaults(): void
    {
        $t = fn (string $key) => fn () => __('transfer::admin.export.entities.'.$key);
        $c = fn (string $key) => __('transfer::admin.columns.'.$key);

        $this->register(new ExportDefinition('orders', $t('orders'), fn () => [
            'number' => [$c('number'), fn (Order $o) => $o->number],
            'created_at' => [$c('created_at'), fn (Order $o) => $o->created_at->format('Y-m-d H:i')],
            'status' => [$c('status'), fn (Order $o) => $o->status->getLabel()],
            'payment_status' => [$c('payment_status'), fn (Order $o) => $o->payment_status->getLabel()],
            'name' => [$c('name'), fn (Order $o) => $o->billing_name],
            'company' => [$c('company'), fn (Order $o) => $o->billing_company],
            'company_id' => [$c('company_id'), fn (Order $o) => $o->billing_company_id],
            'vat_id' => [$c('vat_id'), fn (Order $o) => $o->billing_vat_id],
            'email' => [$c('email'), fn (Order $o) => $o->email],
            'phone' => [$c('phone'), fn (Order $o) => $o->phone],
            'address' => [$c('address'), fn (Order $o) => trim($o->billing_street.', '.$o->billing_zip.' '.$o->billing_city, ', ')],
            'country' => [$c('country'), fn (Order $o) => $o->billing_country],
            'shipping' => [$c('shipping'), fn (Order $o) => $o->shipping_method_name],
            'payment' => [$c('payment'), fn (Order $o) => $o->payment_method_name],
            'items' => [$c('items'), fn (Order $o) => $o->items->map(fn ($item) => $item->quantity.'× '.$item->fullName())->implode('; ')],
            'subtotal' => [$c('subtotal'), fn (Order $o) => self::money($o->subtotal, $o->currency)],
            'discount' => [$c('discount'), fn (Order $o) => self::money($o->discount, $o->currency)],
            'shipping_price' => [$c('shipping_price'), fn (Order $o) => self::money($o->shipping_price, $o->currency)],
            'payment_fee' => [$c('payment_fee'), fn (Order $o) => self::money($o->payment_fee, $o->currency)],
            'total' => [$c('total'), fn (Order $o) => self::money($o->total, $o->currency)],
            'currency' => [$c('currency'), fn (Order $o) => $o->currency],
        ], fn (?string $from, ?string $to) => Order::query()->with('items')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))->orderBy('id'), hasPeriod: true));

        $this->register(new ExportDefinition('products', $t('products'), function () use ($c): array {
            $currency = app(Currencies::class)->default();
            $columns = [
                'id' => [$c('id'), fn (Product $p) => $p->id],
                'sku' => [$c('sku'), fn (Product $p) => $p->sku],
                'ean' => [$c('ean'), fn (Product $p) => $p->ean],
                'name' => [$c('name'), fn (Product $p) => $p->name],
                'short_description' => [$c('short_description'), fn (Product $p) => $p->short_description],
                'description' => [$c('description'), fn (Product $p) => $p->description],
                'price' => [$c('price'), fn (Product $p) => self::money($p->enteredPriceIn($currency), $currency)],
                'compare_at_price' => [$c('compare_at_price'), fn (Product $p) => self::money($p->enteredCompareAtPriceIn($currency), $currency)],
                'stock_quantity' => [$c('stock_quantity'), fn (Product $p) => $p->stock_quantity],
                'is_visible' => [$c('is_visible'), fn (Product $p) => $p->is_visible ? 1 : 0],
                'weight' => [$c('weight'), fn (Product $p) => $p->weight],
                'categories' => [$c('categories'), fn (Product $p) => $p->categories->map(fn ($category) => $category->pathName())->implode(' | ')],
                'manufacturer' => [$c('manufacturer'), fn (Product $p) => $p->manufacturer?->name],
                'url' => [$c('url'), fn (Product $p) => $p->url()],
                'image' => [$c('image'), fn (Product $p) => $p->mainImageUrl()],
            ];

            // Vlastné polia produktu ako ďalšie stĺpce (field:kľúč).
            foreach (CustomField::query()->forEntity(CustomField::PRODUCT)->orderBy('position')->get() as $field) {
                $columns['field:'.$field->key] = [$field->name, fn (Product $p) => $field->display($p->customFieldValues->firstWhere('custom_field_id', $field->id)?->valueIn())];
            }

            return $columns;
        }, fn () => Product::query()->with(['prices', 'categories', 'manufacturer', 'customFieldValues.field'])->orderBy('id')));

        $this->register(new ExportDefinition('stock', $t('stock'), fn () => [
            'sku' => [$c('sku'), fn (array $row) => $row['sku']],
            'name' => [$c('name'), fn (array $row) => $row['name']],
            'variant' => [$c('variant'), fn (array $row) => $row['variant']],
            'stock_quantity' => [$c('stock_quantity'), fn (array $row) => $row['stock']],
        ], fn () => Product::query()->with('variants.values')->where('track_stock', true)->orderBy('id')));

        $this->register(new ExportDefinition('customers', $t('customers'), fn () => [
            'id' => [$c('id'), fn (Customer $u) => $u->id],
            'name' => [$c('name'), fn (Customer $u) => $u->name],
            'email' => [$c('email'), fn (Customer $u) => $u->email],
            'phone' => [$c('phone'), fn (Customer $u) => $u->phone],
            'group' => [$c('group'), fn (Customer $u) => $u->customerGroup?->code],
            'company' => [$c('company'), fn (Customer $u) => $u->billing_address['company'] ?? null],
            'company_id' => [$c('company_id'), fn (Customer $u) => $u->billing_address['company_id'] ?? null],
            'street' => [$c('street'), fn (Customer $u) => $u->billing_address['street'] ?? null],
            'city' => [$c('city'), fn (Customer $u) => $u->billing_address['city'] ?? null],
            'zip' => [$c('zip'), fn (Customer $u) => $u->billing_address['zip'] ?? null],
            'country' => [$c('country'), fn (Customer $u) => $u->billing_address['country'] ?? null],
            'orders_count' => [$c('orders_count'), fn (Customer $u) => $u->orders_count],
            'created_at' => [$c('created_at'), fn (Customer $u) => $u->created_at->format('Y-m-d')],
        ], fn (?string $from, ?string $to) => Customer::query()->with('customerGroup')->withCount('orders')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))->orderBy('id'), hasPeriod: true));

        if (class_exists(Invoice::class)) {
            $this->register(new ExportDefinition('invoices', $t('invoices'), fn () => [
                'number' => [$c('number'), fn ($i) => $i->number],
                'type' => [$c('type'), fn ($i) => $i->type->getLabel()],
                'issued_at' => [$c('issued_at'), fn ($i) => $i->issued_at->format('Y-m-d')],
                'due_at' => [$c('due_at'), fn ($i) => $i->due_at?->format('Y-m-d')],
                'name' => [$c('name'), fn ($i) => $i->customer['company'] ?: ($i->customer['name'] ?? null)],
                'company_id' => [$c('company_id'), fn ($i) => $i->customer['company_id'] ?? null],
                'vat_id' => [$c('vat_id'), fn ($i) => $i->customer['vat_id'] ?? null],
                'order' => [$c('order'), fn ($i) => $i->customer['order_number'] ?? null],
                'total' => [$c('total'), fn ($i) => self::money($i->total, $i->currency)],
                'currency' => [$c('currency'), fn ($i) => $i->currency],
            ], fn (?string $from, ?string $to) => Invoice::query()
                ->when($from, fn ($q) => $q->whereDate('issued_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('issued_at', '<=', $to))->orderBy('issued_at')->orderBy('id'), hasPeriod: true));
        }
    }
}
