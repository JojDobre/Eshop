<?php

namespace Modules\Catalog;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

/**
 * Vytvorí varianty pre všetky kombinácie hodnôt možností produktu (existujúce kombinácie preskočí).
 */
class VariantGenerator
{
    /** Ochrana pred omylom (napr. 5 možností × 10 hodnôt = 100 000 variantov). */
    public const MAX_VARIANTS = 500;

    /**
     * @return int Počet novo vytvorených variantov.
     */
    public function generate(Product $product): int
    {
        $product->load(['options.values', 'variants.values']);

        $valueSets = $product->options
            ->map(fn ($option) => $option->values->pluck('id')->all())
            ->filter(fn (array $ids) => $ids !== [])
            ->values()
            ->all();

        if ($valueSets === []) {
            return 0;
        }

        $combinations = [[]];

        foreach ($valueSets as $ids) {
            $next = [];

            foreach ($combinations as $combination) {
                foreach ($ids as $id) {
                    $next[] = [...$combination, $id];
                }
            }

            $combinations = $next;

            if (count($combinations) > self::MAX_VARIANTS) {
                throw new \RuntimeException(__('catalog::admin.variants.too_many', ['max' => self::MAX_VARIANTS]));
            }
        }

        $existing = $product->variants
            ->map(fn (ProductVariant $variant) => $this->key($variant->values->pluck('id')->all()))
            ->all();

        $created = 0;
        $position = (int) $product->variants->max('position');

        DB::transaction(function () use ($combinations, $existing, $product, &$created, &$position): void {
            foreach ($combinations as $combination) {
                if (in_array($this->key($combination), $existing, true)) {
                    continue;
                }

                $variant = $product->variants()->create(['position' => ++$position, 'stock_quantity' => 0]);
                $variant->values()->attach($combination);
                $created++;
            }
        });

        return $created;
    }

    /**
     * @param  list<int>  $ids
     */
    private function key(array $ids): string
    {
        sort($ids);

        return implode('-', $ids);
    }
}
