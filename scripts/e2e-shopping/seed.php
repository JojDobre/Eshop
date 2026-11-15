<?php

/*
 * Testovacie dáta pre E2E nákupu (spúšťa run.cjs cez „php artisan tinker“ na čistej inštalácii s ukážkovými dátami):
 * variantný produkt, doplnok s textom, kupóny, darčeková poukážka, doprava zadarmo, angličtina a CZK.
 */
use App\Settings\CurrencySettings;
use App\Settings\GeneralSettings;
use Modules\Catalog\Models\Addon;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Catalog\VariantGenerator;
use Modules\Orders\CouponType;
use Modules\Orders\Models\Coupon;
use Modules\Orders\Models\GiftVoucher;
use Modules\Shipping\Models\ShippingMethod;

Product::query()->where('name->sk', 'Tričko E2E')->get()->each->delete();
// Variantný produkt: veľkosť S/M, farba červená/modrá; M/modrá vypredaná, M/červená drahšia.
$tee = Product::query()->create(['name' => 'Tričko E2E', 'stock_quantity' => 0]);
$tee->setPrice('EUR', 1500);
$tee->categories()->attach(Category::query()->first()->id);
$size = $tee->options()->create(['name' => 'Veľkosť', 'position' => 1]);
$size->values()->createMany([['value' => 'S', 'position' => 1], ['value' => 'M', 'position' => 2]]);
$color = $tee->options()->create(['name' => 'Farba', 'position' => 2]);
$color->values()->createMany([['value' => 'červená', 'color' => '#dc2626', 'position' => 1], ['value' => 'modrá', 'color' => '#2563eb', 'position' => 2]]);
app(VariantGenerator::class)->generate($tee);
foreach ($tee->variants()->with('values')->get() as $v) {
    $v->update(['stock_quantity' => $v->name() === 'M / modrá' ? 0 : 5]);
    if ($v->name() === 'M / červená') {
        $v->setPrice('EUR', 1800);
    }
}

// Doplnok s textom (gravírovanie) na mlynček.
$addon = Addon::query()->create(['name' => 'Gravírovanie', 'price' => 500, 'currency' => 'EUR', 'needs_text' => true, 'text_label' => 'Text gravírovania', 'text_max_length' => 30, 'is_active' => true]);
Product::query()->where('id', 7)->first()->addons()->attach($addon);

Coupon::query()->create(['code' => 'ZLAVA10', 'type' => CouponType::Percent, 'value' => 10, 'is_active' => true]);
Coupon::query()->create(['code' => 'MIN100', 'type' => CouponType::Percent, 'value' => 20, 'is_active' => true, 'min_order_amount' => 10000]);
GiftVoucher::query()->create(['code' => 'DAREK2026AB', 'initial_amount' => 1000, 'balance' => 1000, 'currency' => 'EUR', 'is_active' => true]);

// Doprava kuriérom zdarma od 60 €.
ShippingMethod::query()->where('id', 2)->update(['free_from' => 6000]);
// Druhý jazyk a mena (nákup v angličtine a korunách).
$general = app(GeneralSettings::class);
$general->locales = ['en'];
$general->locales_enabled = true;
$general->save();
$currencies = app(CurrencySettings::class);
$currencies->enabled = ['CZK'];
$currencies->rates = ['CZK' => '25.2'];
$currencies->rounding = ['CZK' => 'whole'];
$currencies->multicurrency_enabled = true;
$currencies->save();

echo "seeded\n";
