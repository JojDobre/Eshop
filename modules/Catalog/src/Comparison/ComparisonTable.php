<?php

namespace Modules\Catalog\Comparison;

use Illuminate\Support\Collection;
use Modules\Catalog\CustomFields\CustomFieldType;
use Modules\Catalog\Models\Product;

/**
 * Tabuľka porovnania: produkty rozdelené podľa typu, riadky vlastností so skupinami,
 * označením rozdielov a najlepšej hodnoty.
 */
final class ComparisonTable
{
    /**
     * @param  Collection<int, Product>  $products
     * @return list<array{type: string|null, products: Collection<int, Product>, rows: list<array<string, mixed>>}>
     */
    public static function build(Collection $products): array
    {
        return $products->groupBy(fn (Product $product) => (string) $product->product_type_id)
            ->map(fn (Collection $group) => [
                'type' => $group->first()->productType?->name,
                'products' => $group->values(),
                'rows' => self::rows($group->values()),
            ])->values()->all();
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return list<array{key: string, label: string, group: string|null, values: array<int, string|null>, differs: bool, best: list<int>}>
     */
    private static function rows(Collection $products): array
    {
        $rows = [];
        $general = __('catalog::shop.compare_general');

        // Cena (najlepšia = najnižšia), dostupnosť, výrobca a hodnotenie.
        $prices = $products->mapWithKeys(fn (Product $product) => [$product->id => $product->priceFrom()]);
        $rows[] = self::row('price', __('catalog::shop.compare_price'), $general,
            $prices->map(fn (?int $price, int $id) => $price === null ? null : format_price((int) $products->firstWhere('id', $id)->displayPrice($price)))->all(),
            $prices->filter(fn ($price) => $price !== null)->all(), 'min');
        $rows[] = self::row('availability', __('catalog::shop.compare_availability'), $general,
            $products->mapWithKeys(fn (Product $product) => [$product->id => __($product->isAvailable() ? 'catalog::shop.in_stock' : 'catalog::shop.out_of_stock')])->all());
        $rows[] = self::row('manufacturer', __('catalog::shop.compare_manufacturer'), $general,
            $products->mapWithKeys(fn (Product $product) => [$product->id => $product->manufacturer?->name])->all());

        if ($products->contains(fn (Product $product) => (int) ($product->reviews_count ?? 0) > 0)) {
            $ratings = $products->mapWithKeys(fn (Product $product) => [$product->id => $product->rating_average !== null ? (float) $product->rating_average : null]);
            $rows[] = self::row('rating', __('catalog::shop.compare_rating'), $general,
                $ratings->map(fn (?float $rating, int $id) => $rating === null ? null : number_format($rating, 1, ',', '').' ★ ('.$products->firstWhere('id', $id)->reviews_count.')')->all(),
                $ratings->filter()->all(), 'max');
        }

        // Vlastné polia v poradí a skupinách z administrácie.
        $fields = $products->flatMap(fn (Product $product) => $product->customFieldValues->pluck('field'))
            ->filter(fn ($field) => $field !== null && $field->is_visible)
            ->unique('id')->sortBy(fn ($field) => [$field->position, $field->id])->values();

        foreach ($fields as $field) {
            $values = [];
            $numbers = [];

            foreach ($products as $product) {
                $value = $product->customFieldValues->firstWhere('custom_field_id', $field->id);
                $raw = $value?->valueIn();
                $values[$product->id] = $value ? $field->display($raw) : null;

                if ($field->type === CustomFieldType::Number && is_numeric($raw)) {
                    $numbers[$product->id] = (float) $raw;
                }
            }

            $rows[] = self::row('field_'.$field->id, $field->name, $field->group ?: __('catalog::shop.compare_parameters'), $values, $numbers, $field->compare_best);
        }

        return $rows;
    }

    /**
     * @param  array<int, string|null>  $values
     * @param  array<int, int|float>  $numbers  číselné hodnoty pre „najlepšiu“
     */
    private static function row(string $key, string $label, ?string $group, array $values, array $numbers = [], ?string $best = null): array
    {
        $distinct = array_unique(array_map(fn ($value) => $value ?? '—', $values));
        $winners = [];

        // Najlepšia hodnota má zmysel iba pri rozdielnych hodnotách aspoň dvoch produktov.
        if ($best !== null && count($numbers) > 1 && count(array_unique($numbers)) > 1) {
            $target = $best === 'min' ? min($numbers) : max($numbers);
            $winners = array_keys(array_filter($numbers, fn ($number) => $number == $target));
        }

        return [
            'key' => $key,
            'label' => $label,
            'group' => $group,
            'values' => $values,
            'differs' => count($values) > 1 && count($distinct) > 1,
            'best' => array_values($winners),
        ];
    }
}
