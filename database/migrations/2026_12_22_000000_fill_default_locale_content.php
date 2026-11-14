<?php

use App\Core\Content\ContentTranslationFiller;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Oprava po zmene predvoleného jazyka obchodu vo verzii 1.3.0 a staršej: obsah napísaný v pôvodnom jazyku
     * sa doplní aj do nového predvoleného jazyka (produkty, stránky a adresy sa znova zobrazia).
     */
    public function up(): void
    {
        $json = DB::table('settings')->where('group', 'general')->where('name', 'default_locale')->value('payload');
        $locale = is_string($json) ? json_decode($json, true) : null;

        if (is_string($locale) && $locale !== '') {
            app(ContentTranslationFiller::class)->fill($locale);
        }
    }

    public function down(): void {}
};
