<?php

namespace Modules\Transfer\Exports;

use Generator;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use Modules\Catalog\Models\Product;

/**
 * Vytvorí súbor exportu podľa nastavenia: entita, formát, stĺpce, obdobie, oddeľovač a kódovanie.
 */
final class ExportRunner
{
    public function __construct(private readonly Exports $exports) {}

    /**
     * @param  array{entity: string, format?: string, columns?: list<string>, from?: ?string, to?: ?string, delimiter?: string, encoding?: string}  $config
     * @return array{path: string, name: string}
     */
    public function run(array $config): array
    {
        $definition = $this->exports->find($config['entity'] ?? '') ?? throw new InvalidArgumentException('Unknown export entity.');
        $available = ($definition->columns)();
        $selected = array_values(array_intersect($config['columns'] ?? array_keys($available), array_keys($available))) ?: array_keys($available);
        $format = ExportWriter::extension($config['format'] ?? 'csv');

        $header = collect($selected)->mapWithKeys(fn ($key) => [$key => $available[$key][0]])->all();
        $directory = storage_path('app/private/exports');
        File::ensureDirectoryExists($directory);
        $name = $definition->key.'-'.now()->format('Y-m-d-His').'.'.$format;
        $path = $directory.'/'.bin2hex(random_bytes(4)).'-'.$name;

        ExportWriter::write($path, $format, $header, $this->rows($definition, $available, $selected, $config), (string) ($config['delimiter'] ?? ';'), (string) ($config['encoding'] ?? 'UTF-8'));

        return ['path' => $path, 'name' => $name];
    }

    /**
     * @return Generator<array<string, mixed>>
     */
    private function rows(ExportDefinition $definition, array $available, array $selected, array $config): Generator
    {
        $query = ($definition->query)($config['from'] ?? null, $config['to'] ?? null);

        foreach ($query->lazyById(200) as $record) {
            // Sklad: riadok za produkt bez variantov alebo za každý variant.
            foreach ($definition->key === 'stock' ? self::stockRows($record) : [$record] as $item) {
                yield collect($selected)->mapWithKeys(fn ($key) => [$key => ($available[$key][1])($item)])->all();
            }
        }
    }

    /**
     * @return list<array{sku: ?string, name: string, variant: ?string, stock: int}>
     */
    private static function stockRows(Product $product): array
    {
        if ($product->variants->isEmpty()) {
            return [['sku' => $product->sku, 'name' => $product->name, 'variant' => null, 'stock' => (int) $product->stock_quantity]];
        }

        return $product->variants->map(fn ($variant) => ['sku' => $variant->sku ?? $product->sku, 'name' => $product->name, 'variant' => $variant->name(), 'stock' => (int) $variant->stock_quantity])->all();
    }
}
