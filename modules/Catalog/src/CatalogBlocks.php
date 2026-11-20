<?php

namespace Modules\Catalog;

use App\Core\Blocks\BlockType;
use App\Core\Blocks\BlockTypes;
use App\Core\Blocks\CoreBlocks;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

/**
 * Bloky katalógu: výber produktov (novinky, akcie, kategória, ručný výber) a kategórie.
 */
final class CatalogBlocks
{
    public static function register(BlockTypes $types): void
    {
        $t = fn (string $key) => __('catalog::admin.blocks.'.$key);

        $types->register(new BlockType('products', fn () => $t('products'), fn () => [
            TextInput::make('title')->label(__('admin.blocks.title'))->maxLength(160),
            Select::make('source')->label($t('source'))->options([
                'latest' => $t('sources.latest'), 'bestsellers' => $t('sources.bestsellers'), 'sale' => $t('sources.sale'), 'category' => $t('sources.category'), 'selected' => $t('sources.selected'),
            ])->default('latest')->selectablePlaceholder(false)->live(),
            Select::make('category_id')->label(__('catalog::admin.category.label'))
                ->options(fn () => Category::query()->with('parent.parent')->get()->mapWithKeys(fn (Category $category) => [$category->id => $category->pathName()])->sort()->all())
                ->searchable()->required()->visible(fn (Get $get) => $get('source') === 'category'),
            Select::make('product_ids')->label($t('selected_products'))->multiple()
                ->options(fn () => Product::query()->orderBy('id')->limit(1000)->get()->mapWithKeys(fn (Product $product) => [$product->id => $product->name])->all())
                ->searchable()->required()->visible(fn (Get $get) => $get('source') === 'selected'),
            TextInput::make('limit')->label($t('limit'))->integer()->minValue(1)->maxValue(24)->default(8)
                ->visible(fn (Get $get) => $get('source') !== 'selected'),
            CoreBlocks::hidden(),
        ], prepare: fn (array $data) => [
            ...$data,
            'products' => self::products($data),
            // Odkaz „Zobraziť všetko“ pri produktoch jednej kategórie.
            'link' => ($data['source'] ?? null) === 'category' ? Category::query()->find((int) ($data['category_id'] ?? 0))?->url() : null,
        ], icon: 'heroicon-o-squares-2x2'));

        $types->register(new BlockType('categories', fn () => $t('categories'), fn () => [
            TextInput::make('title')->label(__('admin.blocks.title'))->maxLength(160),
            Select::make('category_ids')->label($t('selected_categories'))->helperText($t('selected_categories_help'))->multiple()
                ->options(fn () => Category::query()->with('parent.parent')->get()->mapWithKeys(fn (Category $category) => [$category->id => $category->pathName()])->sort()->all()),
            CoreBlocks::hidden(),
        ], prepare: fn (array $data) => [...$data, 'categories' => self::categories($data)], icon: 'heroicon-o-folder'));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Product>
     */
    public static function products(array $data): Collection
    {
        $limit = max(1, min(24, (int) ($data['limit'] ?? 8)));
        $query = Product::query()->visible()->with(['prices', 'groupPrices', 'variants.prices', 'variants.groupPrices']);

        return match ($data['source'] ?? 'latest') {
            'category' => ($category = Category::query()->find($data['category_id'] ?? 0))
                ? $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $category->descendantIdsWithSelf()))->latest('id')->limit($limit)->get()
                : collect(),
            'sale' => $query->whereHas('prices', fn ($q) => $q->whereNotNull('compare_at_amount'))->latest('id')->limit($limit)->get(),
            'bestsellers' => self::bestsellers($query, $limit),
            'selected' => ($ids = array_map('intval', (array) ($data['product_ids'] ?? []))) === []
                ? collect()
                : $query->whereIn('id', $ids)->get()->sortBy(fn (Product $product) => array_search($product->id, $ids, true))->values(),
            default => $query->latest('id')->limit($limit)->get(),
        };
    }

    /**
     * Najpredávanejšie produkty (kusy za posledných 90 dní, bez zrušených objednávok). Ak sa ešte nepredávalo
     * dosť produktov, doplnia sa novinky, aby sekcia nebola prázdna.
     *
     * @param  Builder<Product>  $query
     * @return Collection<int, Product>
     */
    public static function bestsellers(Builder $query, int $limit): Collection
    {
        $ids = [];

        if (Schema::hasTable('order_items')) {
            $ids = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.status', '!=', 'cancelled')
                ->where('orders.created_at', '>=', now()->subDays(90))
                ->whereNotNull('order_items.product_id')
                ->groupBy('order_items.product_id')
                ->orderByRaw('SUM(order_items.quantity) DESC')
                ->limit($limit * 2)
                ->pluck('order_items.product_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        $products = $ids === [] ? collect() : (clone $query)->whereIn('id', $ids)->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $ids, true))->values()->take($limit);

        if ($products->count() < $limit) {
            $products = $products->concat((clone $query)->whereNotIn('id', $products->modelKeys())->latest('id')->limit($limit - $products->count())->get());
        }

        return $products->values();
    }

    /**
     * Produkty na úvodnej stránke podľa nastavenia šablóny „Produkty na úvodnej stránke“.
     *
     * @return Collection<int, Product>
     */
    public static function homeProducts(string $source, int $limit = 8): Collection
    {
        return self::products(['source' => in_array($source, ['latest', 'bestsellers', 'sale'], true) ? $source : 'latest', 'limit' => $limit]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Category>
     */
    public static function categories(array $data): Collection
    {
        $ids = array_map('intval', (array) ($data['category_ids'] ?? []));

        if ($ids === []) {
            return Category::query()->visible()->roots()->limit(6)->get();
        }

        return Category::query()->visible()->whereIn('id', $ids)->get()->sortBy(fn (Category $category) => array_search($category->id, $ids, true))->values();
    }
}
