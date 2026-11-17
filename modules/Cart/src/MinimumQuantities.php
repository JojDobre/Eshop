<?php

namespace Modules\Cart;

use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

/**
 * Minimálne množstvo v objednávke.
 *
 * - Produkt: všetky kusy produktu v košíku spolu (aj rôzne varianty), napr. „najmenej 10 ks“.
 * - Kategória: kusy všetkých produktov kategórie a jej podkategórií spolu, napr. 3 vajcia jedného druhu
 *   a 7 iného druhu = 10 ks. Produkty mimo kategórie (napr. klietka) sa nerátajú.
 *
 * Minimum platí len vtedy, keď je v košíku aspoň jeden kus daného produktu alebo kategórie.
 */
class MinimumQuantities
{
    /** @var array<int, array{category: Category, ids: list<int>}>|null */
    private ?array $categoryRules = null;

    /**
     * Nesplnené minimá v košíku.
     *
     * @return list<array{name: string, minimum: int, quantity: int, missing: int, type: 'product'|'category'}>
     */
    public function violations(CartSummary $summary): array
    {
        $violations = [];
        $byProduct = [];

        foreach ($summary->lines as $line) {
            $byProduct[$line->product->id] ??= ['product' => $line->product, 'quantity' => 0];
            $byProduct[$line->product->id]['quantity'] += $line->quantity;
        }

        foreach ($byProduct as ['product' => $product, 'quantity' => $quantity]) {
            $minimum = (int) $product->min_quantity;

            if ($minimum > 1 && $quantity < $minimum) {
                $violations[] = ['name' => (string) $product->name, 'minimum' => $minimum, 'quantity' => $quantity, 'missing' => $minimum - $quantity, 'type' => 'product'];
            }
        }

        $rules = $this->categoryRules();

        if ($rules !== [] && $byProduct !== []) {
            $productCategories = Product::query()->whereKey(array_keys($byProduct))->with('categories:id')->get()
                ->mapWithKeys(fn (Product $product) => [$product->id => $product->categories->pluck('id')->all()]);

            foreach ($rules as ['category' => $category, 'ids' => $ids]) {
                $quantity = 0;

                foreach ($byProduct as $productId => ['quantity' => $productQuantity]) {
                    if (array_intersect($productCategories[$productId] ?? [], $ids) !== []) {
                        $quantity += $productQuantity;
                    }
                }

                if ($quantity > 0 && $quantity < (int) $category->min_quantity) {
                    $violations[] = ['name' => (string) $category->name, 'minimum' => (int) $category->min_quantity, 'quantity' => $quantity, 'missing' => (int) $category->min_quantity - $quantity, 'type' => 'category'];
                }
            }
        }

        return $violations;
    }

    /**
     * Minimum, ktoré sa týka produktu (vlastné alebo z jeho kategórie), pre upozornenie na stránke produktu.
     *
     * @return array{minimum: int, category: Category|null}|null
     */
    public function forProduct(Product $product): ?array
    {
        if ((int) $product->min_quantity > 1) {
            return ['minimum' => (int) $product->min_quantity, 'category' => null];
        }

        $categoryIds = $product->categories()->pluck('categories.id')->all();

        foreach ($this->categoryRules() as ['category' => $category, 'ids' => $ids]) {
            if (array_intersect($categoryIds, $ids) !== []) {
                return ['minimum' => (int) $category->min_quantity, 'category' => $category];
            }
        }

        return null;
    }

    /**
     * Kategórie s minimom a ID ich podkategórií.
     *
     * @return array<int, array{category: Category, ids: list<int>}>
     */
    private function categoryRules(): array
    {
        return $this->categoryRules ??= rescue(fn () => Category::query()->where('min_quantity', '>', 1)->get()
            ->mapWithKeys(fn (Category $category) => [$category->id => ['category' => $category, 'ids' => $category->descendantIdsWithSelf()]])
            ->all(), [], false);
    }
}
