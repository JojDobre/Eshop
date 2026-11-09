<?php

/*
 * Testovacie dáta pre záťažový test (PLAN.md, kapitola 18): 10 000 produktov, 30 000 variantov
 * cien, 200 kategórií a 50 000 objednávok. Iba pre testovaciu databázu – nikdy nie na ostrom obchode!
 *
 * Použitie: php scripts/perf-seed.php [produkty] [objednávky]
 */
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

$products = (int) ($argv[1] ?? 10000);
$orders = (int) ($argv[2] ?? 50000);
$locale = (string) json_decode((string) DB::table('settings')->where('name', 'default_locale')->value('payload')) ?: 'sk';
$currency = (string) json_decode((string) DB::table('settings')->where('name', 'default_currency')->value('payload')) ?: 'EUR';
$now = now()->toDateTimeString();
$json = fn (string $value) => json_encode([$locale => $value], JSON_UNESCAPED_UNICODE);

echo "Kategórie…\n";
$categoryIds = [];
for ($i = 1; $i <= 200; $i++) {
    $categoryIds[] = DB::table('categories')->insertGetId(['name' => $json("Kategória $i"), 'slug' => $json("perf-kategoria-$i"), 'position' => $i, 'created_at' => $now, 'updated_at' => $now]);
}

echo "Produkty…\n";
$firstProduct = (int) DB::table('products')->max('id') + 1;
foreach (array_chunk(range(1, $products), 500) as $chunk) {
    DB::table('products')->insert(array_map(fn (int $i) => [
        'name' => $json("Produkt $i"), 'slug' => $json("perf-produkt-$i"), 'sku' => "PERF-$i",
        'description' => $json('<p>'.Str::repeat('Popis produktu. ', 30).'</p>'),
        'stock_quantity' => random_int(0, 100), 'created_at' => $now, 'updated_at' => $now,
    ], $chunk));
}
$productIds = range($firstProduct, $firstProduct + $products - 1);

echo "Ceny a kategórie produktov…\n";
foreach (array_chunk($productIds, 1000) as $chunk) {
    DB::table('product_prices')->insert(array_map(fn (int $id) => ['product_id' => $id, 'currency' => $currency, 'amount' => random_int(100, 20000), 'created_at' => $now, 'updated_at' => $now], $chunk));
    DB::table('category_product')->insert(array_map(fn (int $id) => ['category_id' => $categoryIds[array_rand($categoryIds)], 'product_id' => $id, 'position' => 0], $chunk));
}

echo "Objednávky…\n";
$start = (int) DB::table('orders')->max('id');
foreach (array_chunk(range(1, $orders), 1000) as $chunk) {
    DB::table('orders')->insert(array_map(fn (int $i) => [
        'number' => 'P'.str_pad((string) ($start + $i), 8, '0', STR_PAD_LEFT), 'token' => Str::random(40), 'status' => 'completed', 'payment_status' => 'paid',
        'locale' => $locale, 'currency' => $currency, 'exchange_rate' => 1, 'vat_payer' => 1, 'tax_mode' => 'standard', 'email' => "zakaznik$i@example.com",
        'billing_name' => "Zákazník $i", 'billing_street' => 'Hlavná 1', 'billing_city' => 'Bratislava', 'billing_zip' => '81101', 'billing_country' => 'SK',
        'shipping_method_name' => 'Kuriér', 'shipping_type' => 'delivery', 'shipping_tax_rate' => 23, 'shipping_price' => 400,
        'payment_method_name' => 'Prevodom', 'payment_type' => 'bank_transfer', 'payment_tax_rate' => 23, 'payment_fee' => 0,
        'subtotal' => 2000, 'total' => 2400, 'created_at' => now()->subMinutes($i)->toDateTimeString(), 'updated_at' => $now,
    ], $chunk));
}

echo "✓ Hotovo: $products produktov, 200 kategórií, $orders objednávok\n";
