<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Webhosting bez možnosti nastaviť DocumentRoot na public/: požiadavky sem presmeruje
// súbor .htaccess v koreni e-shopu. Adresa však „/public" neobsahuje, preto Laravelu
// oznámime skutočnú základnú cestu (napr. /obchod namiesto /obchod/public).
(function (): void {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $publicBase = rtrim(dirname($script), '/');

    if (str_ends_with($publicBase, '/public') && ! str_starts_with($uri, $publicBase.'/') && $uri !== $publicBase) {
        $rootScript = substr($publicBase, 0, -strlen('/public')).'/index.php';
        $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'] = $rootScript;
    }
})();

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
