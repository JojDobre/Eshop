import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
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
});
