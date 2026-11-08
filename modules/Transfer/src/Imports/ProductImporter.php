<?php

namespace Modules\Transfer\Imports;

use App\Core\Support\Currencies;
use App\Core\Support\Price;
use Illuminate\Support\Str;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldValues;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Manufacturer;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductOption;
use Modules\Catalog\Models\ProductOptionValue;
use Modules\Catalog\Models\ProductVariant;

/**
 * Produkty a varianty podľa SKU. Riadok s „parent_sku“ je variant produktu s daným SKU,
 * jeho voľby sú v „variant_options“ (napr. „Veľkosť=M; Farba=červená“).
 * Kategórie ako cesta „Káva > Zrnková“, viac kategórií oddelených „|“. Vlastné polia: field:kľúč.
 */
class ProductImporter implements Importer
{
    public function key(): string
    {
        return 'products';
    }

    public function label(): string
    {
        return __('transfer::admin.export.entities.products');
    }

    public function keyField(): string
    {
        return 'sku';
    }

    public function fields(): array
    {
        $c = fn (string $key) => __('transfer::admin.columns.'.$key);
        $fields = collect(['sku', 'parent_sku', 'variant_options', 'name', 'ean', 'short_description', 'description', 'price', 'compare_at_price',
            'stock_quantity', 'is_visible', 'weight', 'categories', 'manufacturer'])->mapWithKeys(fn ($key) => [$key => $c($key)])->all();

        foreach (CustomField::query()->forEntity(CustomField::PRODUCT)->orderBy('position')->get() as $field) {
            $fields['field:'.$field->key] = $field->name;
        }

        return $fields;
    }

    public function validate(array $data): array
    {
        $errors = [];

        if (blank($data['sku'] ?? null)) {
            $errors[] = __('transfer::admin.import.errors.sku');
        }

        if (blank($data['parent_sku'] ?? null) && blank($data['name'] ?? null) && ! Product::query()->where('sku', $data['sku'] ?? '')->exists()) {
            $errors[] = __('transfer::admin.import.errors.name');
        }

        foreach (['price', 'compare_at_price', 'weight'] as $field) {
            if (filled($data[$field] ?? null) && ! is_numeric(str_replace([' ', ','], ['', '.'], $data[$field]))) {
                $errors[] = __('transfer::admin.import.errors.number', ['field' => $field]);
            }
        }

        if (filled($data['stock_quantity'] ?? null) && ! preg_match('/^-?\d+$/', $data['stock_quantity'])) {
            $errors[] = __('transfer::admin.import.errors.number', ['field' => 'stock_quantity']);
        }

        if (filled($data['parent_sku'] ?? null) && ! Product::query()->where('sku', $data['parent_sku'])->exists()) {
            $errors[] = __('transfer::admin.import.errors.parent', ['sku' => $data['parent_sku']]);
        }

        return $errors;
    }

    public function import(array $data): bool
    {
        return filled($data['parent_sku'] ?? null) ? $this->variant($data) : $this->product($data);
    }

    private function product(array $data): bool
    {
        $product = Product::query()->where('sku', $data['sku'])->first();
        $created = $product === null;
        $product ??= new Product(['sku' => $data['sku']]);
        $attributes = array_filter([
            'name' => $data['name'] ?? null,
            'ean' => $data['ean'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'weight' => filled($data['weight'] ?? null) ? (int) round((float) str_replace(',', '.', $data['weight'])) : null,
            'manufacturer_id' => filled($data['manufacturer'] ?? null)
                ? Manufacturer::query()->firstOrCreate(['name' => $data['manufacturer']])->id : null,
        ], fn ($value) => $value !== null && $value !== '');

        if (array_key_exists('is_visible', $data) && $data['is_visible'] !== '') {
            $attributes['is_visible'] = self::bool($data['is_visible']);
        }

        if (filled($data['stock_quantity'] ?? null)) {
            $attributes['stock_quantity'] = (int) $data['stock_quantity'];
        }

        $product->fill($attributes)->save();
        $currency = app(Currencies::class)->default();

        if (filled($data['price'] ?? null)) {
            $product->setPrice($currency, self::money($data['price'], $currency), filled($data['compare_at_price'] ?? null) ? self::money($data['compare_at_price'], $currency) : null);
        }

        if (filled($data['categories'] ?? null)) {
            $product->categories()->sync(collect(explode('|', $data['categories']))->map(fn ($path) => self::category($path)?->id)->filter()->unique()->values()->all());
        }

        $fields = collect($data)->filter(fn ($value, $key) => str_starts_with((string) $key, 'field:') && $value !== '')
            ->mapWithKeys(fn ($value, $key) => [substr((string) $key, 6) => $value])->all();

        if ($fields !== []) {
            app(CustomFieldValues::class)->save(CustomField::PRODUCT, $product->id, $fields);
        }

        return $created;
    }

    private function variant(array $data): bool
    {
        $product = Product::query()->where('sku', $data['parent_sku'])->firstOrFail();
        $variant = ProductVariant::query()->where('sku', $data['sku'])->first();
        $created = $variant === null;
        $variant ??= ProductVariant::query()->create(['product_id' => $product->id, 'sku' => $data['sku'], 'position' => (int) $product->variants()->max('position') + 1]);

        if (filled($data['variant_options'] ?? null)) {
            $valueIds = [];

            foreach (explode(';', $data['variant_options']) as $pair) {
                [$optionName, $valueName] = array_pad(array_map('trim', explode('=', $pair, 2)), 2, '');

                if ($optionName === '' || $valueName === '') {
                    continue;
                }

                $option = $product->options()->get()->first(fn (ProductOption $option) => mb_strtolower($option->name) === mb_strtolower($optionName))
                    ?? ProductOption::query()->create(['product_id' => $product->id, 'name' => $optionName, 'position' => (int) $product->options()->max('position') + 1]);
                $value = $option->values()->get()->first(fn (ProductOptionValue $value) => mb_strtolower($value->value) === mb_strtolower($valueName))
                    ?? $option->values()->create(['value' => $valueName, 'position' => (int) $option->values()->max('position') + 1]);
                $valueIds[] = $value->id;
            }

            $variant->values()->sync($valueIds);
        }

        if (filled($data['stock_quantity'] ?? null)) {
            $variant->update(['stock_quantity' => (int) $data['stock_quantity']]);
        }

        if (array_key_exists('is_visible', $data) && $data['is_visible'] !== '') {
            $variant->update(['is_active' => self::bool($data['is_visible'])]);
        }

        if (filled($data['price'] ?? null)) {
            $currency = app(Currencies::class)->default();
            $variant->setPrice($currency, self::money($data['price'], $currency));
        }

        return $created;
    }

    /** Kategória podľa cesty „Rodič > Dieťa“ (chýbajúce sa vytvoria). */
    private static function category(string $path): ?Category
    {
        $parent = null;

        foreach (array_filter(array_map('trim', preg_split('/\s*[>›]\s*/u', $path) ?: [])) as $name) {
            $parent = Category::query()->where('parent_id', $parent?->id)->get()->first(fn (Category $category) => mb_strtolower($category->name) === mb_strtolower($name))
                ?? Category::query()->create(['name' => $name, 'parent_id' => $parent?->id]);
        }

        return $parent;
    }

    private static function money(string $value, string $currency): int
    {
        return Price::toMinor(str_replace([' ', "\u{00A0}"], '', str_replace(',', '.', $value)), $currency);
    }

    private static function bool(string $value): bool
    {
        return in_array(Str::lower(trim($value)), ['1', 'true', 'ano', 'áno', 'yes', 'y', 'a'], true);
    }
}
