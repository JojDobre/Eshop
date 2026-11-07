<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Výrobcovia a zodpovedné osoby (nariadenie o všeobecnej bezpečnosti výrobkov – GPSR).
        Schema::create('manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            // Zodpovedná osoba v EÚ, ak výrobca nesídli v EÚ (meno, adresa, kontakt).
            $table->text('eu_responsible_person')->nullable();
            // Predvolené bezpečnostné informácie pre produkty výrobcu.
            $table->json('safety_info')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('tax_rate_id')->nullable()->after('weight')->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('manufacturer_id')->nullable()->after('tax_rate_id')->constrained()->nullOnDelete();
            $table->json('safety_info')->nullable()->after('description');
            // Upozornenie na nízky stav skladu (null = predvolená hodnota z nastavení).
            $table->unsignedInteger('low_stock_threshold')->nullable()->after('stock_quantity');
        });

        // Pôvodná (prečiarknutá) cena pri zľave.
        Schema::table('product_prices', function (Blueprint $table) {
            $table->unsignedBigInteger('compare_at_amount')->nullable()->after('amount');
        });

        // História cien – kvôli zobrazeniu najnižšej ceny za posledných 30 dní pri zľave (smernica Omnibus).
        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->char('currency', 3);
            $table->unsignedBigInteger('amount');
            $table->timestamp('recorded_at');

            $table->index(['product_id', 'currency', 'recorded_at']);
        });

        // Každá zmena stavu skladu so zdôvodnením (objednávka, storno, ručná úprava…).
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->integer('stock_after');
            $table->string('type', 32);
            $table->string('reference', 64)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('product_price_history');
        Schema::table('product_prices', fn (Blueprint $table) => $table->dropColumn('compare_at_amount'));
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tax_rate_id');
            $table->dropConstrainedForeignId('manufacturer_id');
            $table->dropColumn(['safety_info', 'low_stock_threshold']);
        });
        Schema::dropIfExists('manufacturers');
    }
};
