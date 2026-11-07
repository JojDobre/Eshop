<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Typy produktov: aké vlastné polia produkt má a ako sa predáva.
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            // Potrebuje tovar dopravu? (služby a digitálne produkty nie)
            $table->boolean('requires_shipping')->default(true);
            // Jedinečný kus (napr. zviera): vždy 1 kus na sklade a v košíku najviac 1.
            $table->boolean('is_unique')->default(false);
            // Merná jednotka pre cenu za jednotku: pcs, kg, l, m.
            $table->string('unit', 8)->default('pcs');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_type_custom_field', function (Blueprint $table) {
            $table->foreignId('product_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('custom_field_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_type_id', 'custom_field_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_type_id')->nullable()->after('id')->constrained()->nullOnDelete();
            // Obsah balenia v mernej jednotke typu (napr. 0,25 kg) – z neho sa počíta cena za jednotku.
            $table->decimal('unit_amount', 12, 4)->nullable()->after('weight');
        });

        // Predpripravené typy (názvy v slovenčine, češtine a angličtine).
        $types = [
            ['Všeobecný tovar', 'Všeobecné zboží', 'General goods', true, false, 'pcs'],
            ['Oblečenie a obuv', 'Oblečení a obuv', 'Clothing and footwear', true, false, 'pcs'],
            ['Nábytok', 'Nábytek', 'Furniture', true, false, 'pcs'],
            ['Potraviny', 'Potraviny', 'Food', true, false, 'kg'],
            ['Kozmetika a drogéria', 'Kosmetika a drogerie', 'Cosmetics', true, false, 'l'],
            ['Zvieratá', 'Zvířata', 'Animals', true, true, 'pcs'],
            ['Služby', 'Služby', 'Services', false, false, 'pcs'],
            ['Digitálne produkty', 'Digitální produkty', 'Digital products', false, false, 'pcs'],
        ];

        foreach ($types as $position => [$sk, $cs, $en, $shipping, $unique, $unit]) {
            DB::table('product_types')->insert([
                'name' => json_encode(['sk' => $sk, 'cs' => $cs, 'en' => $en], JSON_UNESCAPED_UNICODE),
                'requires_shipping' => $shipping,
                'is_unique' => $unique,
                'unit' => $unit,
                'position' => $position,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_type_id');
            $table->dropColumn('unit_amount');
        });
        Schema::dropIfExists('product_type_custom_field');
        Schema::dropIfExists('product_types');
    }
};
