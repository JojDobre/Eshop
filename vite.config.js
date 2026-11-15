import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ command }) => ({
    // Relatívne cesty v CSS (písma, obrázky), aby fungovali aj v podadresári, napr. mojweb.sk/obchod.
    base: command === 'build' ? './' : undefined,
    plugins: [
        laravel({
            input: [
                // Inštalátor
                'resources/css/installer.css',
                // Písmo administrácie (uložené na hostingu, nie z externej služby)
                'resources/css/admin-font.css',
                // Téma administrácie (Filament)
                'resources/css/filament/admin/theme.css',
                // Predvolená šablóna obchodu
                'themes/default/assets/css/theme.css',
                'themes/default/assets/js/theme.js',
                // Šablóna Fresh (odvodená od Default)
                'themes/fresh/assets/css/theme.css',
                // Šablóna Farma (odvodená od Default)
                'themes/farma/assets/css/theme.css',
                // Šablóna Dielňa (handmade, odvodená od Default)
                'themes/dielna/assets/css/theme.css',
                // Šablóna Štúdio (služby a rezervácie, odvodená od Default)
                'themes/studio/assets/css/theme.css',
                // Šablóna Esencia (kozmetika a drogéria, odvodená od Default)
                'themes/esencia/assets/css/theme.css',
                // Šablóna Butik (móda, odvodená od Default)
                'themes/butik/assets/css/theme.css',
                // Šablóna Lupeň (kvetinárstvo, odvodená od Default)
                'themes/lupen/assets/css/theme.css',
                // Šablóna Kapitola (kníhkupectvo, odvodená od Default)
                'themes/kapitola/assets/css/theme.css',
            ],
            refresh: ['resources/views/**', 'themes/**/*.blade.php', 'modules/**/*.blade.php'],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
}));
