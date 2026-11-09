<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Pages\Listeners\CreateDefaultPages;

return new class extends Migration
{
    /**
     * Existujúcim obchodom doplní kostru vyhlásenia o prístupnosti (nové inštalácie ju dostanú pri inštalácii).
     */
    public function up(): void
    {
        if (! DB::table('pages')->exists() || DB::table('pages')->where('role', 'accessibility')->exists()) {
            return;
        }

        $payload = DB::table('settings')->where('group', 'general')->where('name', 'default_locale')->value('payload');
        $locale = is_string($payload) ? (string) json_decode($payload) : 'sk';
        $locale = in_array($locale, ['sk', 'cs', 'en'], true) ? $locale : 'en';
        $names = ['sk' => 'Vyhlásenie o prístupnosti', 'cs' => 'Prohlášení o přístupnosti', 'en' => 'Accessibility statement'];
        $slugs = ['sk' => 'vyhlasenie-o-pristupnosti', 'cs' => 'prohlaseni-o-pristupnosti', 'en' => 'accessibility-statement'];

        DB::table('pages')->insert([
            'name' => json_encode([$locale => $names[$locale]], JSON_UNESCAPED_UNICODE),
            'slug' => json_encode([$locale => $slugs[$locale]]),
            'content' => json_encode([$locale => CreateDefaultPages::ACCESSIBILITY[$locale]], JSON_UNESCAPED_UNICODE),
            'role' => 'accessibility',
            'is_visible' => true,
            'show_in_footer' => true,
            'position' => (int) DB::table('pages')->max('position') + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
