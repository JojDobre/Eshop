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
                // Predvolená šablóna obchodu
                'themes/default/assets/css/theme.css',
                'themes/default/assets/js/theme.js',
                // Šablóna Fresh (odvodená od Default)
                'themes/fresh/assets/css/theme.css',
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
