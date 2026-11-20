<?php

/*
 * Testovacie dáta pre testy v prehliadači (spúšťa ich tests/e2e/setup/install.setup.js cez „php artisan tinker“).
 * Opakované spustenie nič nezdvojí. Na konci vypíše JSON s adresami, ktoré testy používajú.
 */
use App\Settings\CompanySettings;
use App\Settings\GeneralSettings;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

$company = app(CompanySettings::class);
$company->company_name = $company->company_name ?: 'E2E Farma';
$company->iban = 'SK3112000000198742637541';
$company->swift = 'TATRSKBX';
$company->save();

// Kategória so spoločným minimom 10 ks (druhy sa sčítajú) a produkt mimo nej (klietka sa do minima neráta).
$category = Category::query()->where('name->sk', 'E2E vajcia')->first()
    ?? Category::query()->create(['name' => 'E2E vajcia', 'is_visible' => true]);
$category->update(['min_quantity' => 10]);

$make = function (string $name, string $price, ?Category $category = null): Product {
    $product = Product::query()->where('name->sk', $name)->first()
        ?? Product::query()->create(['name' => $name, 'stock_quantity' => 500, 'is_visible' => true]);
    $product->update(['stock_quantity' => 500, 'is_visible' => true, 'track_stock' => true]);
    $product->setPrice(app(GeneralSettings::class)->default_currency, (int) round((float) $price * 100));
    $category && $product->categories()->syncWithoutDetaching([$category->id]);

    return $product;
};

$white = $make('E2E vajcia biele', '1.00', $category);
$brown = $make('E2E vajcia hnedé', '1.20', $category);
$cage = $make('E2E klietka', '25.00');

$path = fn (Product $product) => parse_url($product->url(), PHP_URL_PATH);

echo json_encode([
    'white' => $path($white),
    'brown' => $path($brown),
    'cage' => $path($cage),
    'category' => 'E2E vajcia',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL;
