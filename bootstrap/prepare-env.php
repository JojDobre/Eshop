<?php

/*
|--------------------------------------------------------------------------
| Príprava súboru .env pri prvom spustení
|--------------------------------------------------------------------------
|
| Na webhostingu sa e-shop nahrá bez súboru .env. Pri prvej požiadavke sa
| vytvorí z .env.example a vygeneruje sa náhodný aplikačný kľúč, aby mohol
| bežať webový inštalátor (sessions a CSRF ochrana potrebujú kľúč).
|
*/

(function (string $basePath): void {
    $env = $basePath.'/.env';
    $example = $basePath.'/.env.example';

    if (is_file($env) || ! is_file($example) || getenv('APP_ENV') === 'testing') {
        return;
    }

    if (! is_writable($basePath)) {
        return; // Inštalátor zobrazí, že adresár nie je zapisovateľný.
    }

    $key = 'base64:'.base64_encode(random_bytes(32));
    $contents = (string) file_get_contents($example);
    $contents = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $contents, 1) ?? $contents;

    file_put_contents($env, $contents, LOCK_EX);
    @chmod($env, 0600);
})(dirname(__DIR__));
