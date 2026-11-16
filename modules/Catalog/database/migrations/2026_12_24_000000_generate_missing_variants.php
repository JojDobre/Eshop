<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Catalog\Models\Product;
use Modules\Catalog\VariantGenerator;

/**
 * Produkty s možnosťami (napr. Farba) bez variantov: vytvoria sa varianty, aby sa možnosti zobrazili na stránke produktu.
 * Predtým sa varianty vytvárali len tlačidlom „Vygenerovať varianty“ a bez neho sa možnosti v obchode neukázali.
 */
return new class extends Migration
{
    public function up(): void
    {
        $generator = app(VariantGenerator::class);

        Product::query()->whereHas('options.values')->whereDoesntHave('variants')->each(function (Product $product) use ($generator): void {
            rescue(fn () => $generator->generate($product), report: true);
        });
    }

    public function down(): void
    {
        // Vytvorené varianty zostanú – obchodník ich mohol medzitým upraviť.
    }
};
