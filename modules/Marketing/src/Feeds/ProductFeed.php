<?php

namespace Modules\Marketing\Feeds;

use App\Core\Support\CurrencyContext;
use App\Core\Support\Price;
use Generator;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use XMLWriter;

/**
 * XML feedy produktov: Heureka (SK/CZ) a Google Merchant (formát RSS s g:, ktorý číta aj Facebook/Meta katalóg).
 * Varianty sú samostatné položky so spoločným ITEMGROUP_ID / g:item_group_id.
 */
class ProductFeed
{
    public const TYPES = ['heureka', 'google', 'facebook'];

    public function __construct(private readonly bool $includeOutOfStock = true) {}

    /**
     * Zapíše feed do súboru (po dávkach, aby stačila pamäť aj pri veľkom katalógu).
     */
    public function write(string $type, string $path): void
    {
        $xml = new XMLWriter;
        $xml->openUri($path);
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        if ($type === 'heureka') {
            $xml->startElement('SHOP');

            foreach ($this->items() as $item) {
                $this->heurekaItem($xml, $item);
            }
        } else {
            $xml->startElement('rss');
            $xml->writeAttribute('version', '2.0');
            $xml->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
            $xml->startElement('channel');
            $xml->writeElement('title', (string) config('app.name'));
            $xml->writeElement('link', url('/'));
            $xml->writeElement('description', (string) config('app.name'));

            foreach ($this->items() as $item) {
                $this->googleItem($xml, $item);
            }

            $xml->endElement();
        }

        $xml->endElement();
        $xml->endDocument();
        $xml->flush();
    }

    /**
     * Položky feedu: produkt bez variantov alebo každý aktívny variant zvlášť.
     *
     * @return Generator<array<string, mixed>>
     */
    public function items(): Generator
    {
        $currency = app(CurrencyContext::class)->current();
        $paths = Category::query()->get()->mapWithKeys(fn (Category $category) => [$category->id => $category->pathName()]);

        $query = Product::query()->visible()->with([
            'prices', 'groupPrices', 'taxRate', 'manufacturer', 'categories', 'customFieldValues.field',
            'variants' => fn ($variants) => $variants->where('is_active', true)->with(['prices', 'groupPrices', 'values.option']),
        ]);

        foreach ($query->lazyById(200) as $product) {
            $category = $product->categories->first();
            $base = [
                'group' => null,
                'name' => $product->name,
                'description' => Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags((string) ($product->description ?: $product->short_description)))), 4900, ''),
                'url' => $product->url(),
                'images' => $product->imageUrls(),
                'manufacturer' => $product->manufacturer?->name,
                'category' => $category ? ($paths[$category->id] ?? null) : null,
                'params' => $product->visibleParameters(),
                'currency' => $currency,
            ];

            if ($product->hasVariants()) {
                foreach ($product->variants as $variant) {
                    $variant->setRelation('product', $product);
                    $item = $this->variantItem($product, $variant, $base);

                    if ($item !== null) {
                        yield $item;
                    }
                }

                continue;
            }

            $price = $product->priceIn($currency);

            if ($price === null || (! $this->includeOutOfStock && ! $product->isAvailable())) {
                continue;
            }

            yield [...$base,
                'id' => (string) $product->id,
                'price' => $price,
                'regular' => $product->compareAtPriceIn($currency),
                'available' => $product->isAvailable(),
                'preorder' => $product->isPreorder(),
                'sku' => $product->sku,
                'ean' => $product->ean,
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $base
     * @return array<string, mixed>|null
     */
    private function variantItem(Product $product, ProductVariant $variant, array $base): ?array
    {
        $price = $variant->priceIn($base['currency']);

        if ($price === null || (! $this->includeOutOfStock && ! $variant->isAvailable())) {
            return null;
        }

        return [...$base,
            'id' => $product->id.'-'.$variant->id,
            'group' => (string) $product->id,
            'name' => trim($product->name.' '.$variant->name()),
            'images' => array_filter($variant->images ?? []) !== [] ? $variant->imageUrls() : $base['images'],
            'price' => $price,
            'regular' => null,
            'available' => $variant->isAvailable(),
            'preorder' => $variant->isPreorder(),
            'sku' => $variant->sku ?? $product->sku,
            'ean' => $variant->ean ?? null,
            'params' => [...$base['params'], ...$variant->values->mapWithKeys(fn ($value) => [(string) ($value->option?->name ?? '') => $value->value])->filter(fn ($value, $key) => $key !== '')->all()],
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function heurekaItem(XMLWriter $xml, array $item): void
    {
        $xml->startElement('SHOPITEM');
        $xml->writeElement('ITEM_ID', $item['id']);
        $xml->writeElement('PRODUCTNAME', $item['name']);
        $xml->writeElement('DESCRIPTION', $item['description']);
        $xml->writeElement('URL', $item['url']);

        foreach (array_values($item['images']) as $index => $image) {
            $xml->writeElement($index === 0 ? 'IMGURL' : 'IMGURL_ALTERNATIVE', $image);
        }

        $xml->writeElement('PRICE_VAT', Price::toDecimal($item['price'], $item['currency']));
        $this->optional($xml, 'MANUFACTURER', $item['manufacturer']);
        $this->optional($xml, 'CATEGORYTEXT', $item['category'] ? str_replace(' › ', ' | ', $item['category']) : null);
        $this->optional($xml, 'EAN', $item['ean']);
        $this->optional($xml, 'PRODUCTNO', $item['sku']);
        $this->optional($xml, 'ITEMGROUP_ID', $item['group']);
        // Dodacia lehota v dňoch: 0 = skladom.
        $xml->writeElement('DELIVERY_DATE', $item['available'] && ! $item['preorder'] ? '0' : '7');

        foreach ($item['params'] as $name => $value) {
            $xml->startElement('PARAM');
            $xml->writeElement('PARAM_NAME', (string) $name);
            $xml->writeElement('VAL', (string) $value);
            $xml->endElement();
        }

        $xml->endElement();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function googleItem(XMLWriter $xml, array $item): void
    {
        $money = fn (int $amount) => Price::toDecimal($amount, $item['currency']).' '.$item['currency'];
        $onSale = $item['regular'] !== null && $item['regular'] > $item['price'];

        $xml->startElement('item');
        $xml->writeElement('g:id', $item['id']);
        $xml->writeElement('g:title', Str::limit($item['name'], 150, ''));
        $xml->writeElement('g:description', $item['description'] !== '' ? $item['description'] : $item['name']);
        $xml->writeElement('g:link', $item['url']);

        foreach (array_values($item['images']) as $index => $image) {
            $xml->writeElement($index === 0 ? 'g:image_link' : 'g:additional_image_link', $image);
        }

        $xml->writeElement('g:availability', $item['preorder'] ? 'preorder' : ($item['available'] ? 'in_stock' : 'out_of_stock'));
        $xml->writeElement('g:price', $money($onSale ? $item['regular'] : $item['price']));

        if ($onSale) {
            $xml->writeElement('g:sale_price', $money($item['price']));
        }

        $xml->writeElement('g:condition', 'new');
        $this->optional($xml, 'g:brand', $item['manufacturer']);
        $this->optional($xml, 'g:gtin', $item['ean']);
        $this->optional($xml, 'g:mpn', $item['sku']);
        $this->optional($xml, 'g:product_type', $item['category'] ? str_replace(' › ', ' > ', $item['category']) : null);
        $this->optional($xml, 'g:item_group_id', $item['group']);

        // Bez EAN a bez značky s kódom výrobcu Google vyžaduje „identifier_exists = no“.
        if (blank($item['ean']) && (blank($item['manufacturer']) || blank($item['sku']))) {
            $xml->writeElement('g:identifier_exists', 'no');
        }

        $xml->endElement();
    }

    private function optional(XMLWriter $xml, string $name, ?string $value): void
    {
        if (filled($value)) {
            $xml->writeElement($name, $value);
        }
    }
}
