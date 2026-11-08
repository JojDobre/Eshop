<?php

namespace App\Http\Controllers;

use App\Core\Themes\ThemeManager;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Súbory šablóny bez kompilácie a bez symbolických odkazov: obrázky, písma, CSS a JS
 * z priečinka assets/ (pre nahraté šablóny) a náhľad screenshot pre administráciu.
 */
class ThemeAssetController extends Controller
{
    private const TYPES = [
        'css' => 'text/css', 'js' => 'application/javascript', 'map' => 'application/json', 'json' => 'application/json',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp', 'gif' => 'image/gif',
        'svg' => 'image/svg+xml', 'ico' => 'image/x-icon', 'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
    ];

    public function asset(string $theme, string $path): BinaryFileResponse
    {
        $found = app(ThemeManager::class)->find($theme);
        abort_if($found === null || str_contains($path, '..'), 404);

        return $this->serve($found->path.'/assets/'.$path);
    }

    public function screenshot(string $theme): BinaryFileResponse
    {
        $found = app(ThemeManager::class)->find($theme);
        abort_if($found === null || $found->screenshot === null, 404);

        return $this->serve($found->path.'/'.$found->screenshot);
    }

    private function serve(string $file): BinaryFileResponse
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        abort_unless(is_file($file) && isset(self::TYPES[$extension]), 404);

        return response()->file($file, [
            'Content-Type' => self::TYPES[$extension],
            'Cache-Control' => 'public, max-age=604800',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
