<?php

namespace App\Http\Middleware;

use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Filament\Pages\CustomizeTheme;
use App\Models\BlockLayout;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Náhľad šablóny pred aktiváciou a živý náhľad prispôsobenia – iba pre administrátora.
 *
 * ?theme_preview=nazov zapne náhľad šablóny (platí do ukončenia), ?theme_preview=0 ho ukončí.
 * ?theme_customize=1 použije neuložené hodnoty z prispôsobenia (posiela ich administrácia do relácie).
 */
class ApplyThemePreview
{
    public const SESSION_THEME = 'eshop.theme_preview';

    public const SESSION_VALUES = 'eshop.theme_customize';

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*', 'livewire/*', 'install', 'install/*') || ! $request->hasSession()) {
            return $next($request);
        }

        app(ThemeOptions::class)->preview(null);
        BlockLayout::preview(BlockLayout::HOME, null);
        $this->usePreview(null);
        $user = $request->user();
        $allowed = $user !== null && method_exists($user, 'can') && $user->can('settings.manage');

        if (! $allowed) {
            return $next($request);
        }

        if ($request->has('theme_preview')) {
            $name = (string) $request->query('theme_preview');
            $name === '0' || $name === '' ? $request->session()->forget(self::SESSION_THEME) : $request->session()->put(self::SESSION_THEME, $name);
        }

        $themes = app(ThemeManager::class);
        $preview = $request->session()->get(self::SESSION_THEME);

        if (is_string($preview) && $preview !== $themes->activeName() && $themes->find($preview) !== null) {
            $this->usePreview($preview);
        }

        if ($request->boolean('theme_customize')) {
            $values = $request->session()->get(self::SESSION_VALUES.'.'.$themes->active()?->name);
            app(ThemeOptions::class)->preview(is_array($values) ? $values : null);

            // Neuložené bloky úvodnej stránky z prispôsobenia šablóny.
            if (is_array($values) && is_array($values[CustomizeTheme::BLOCKS_FIELD] ?? null)) {
                BlockLayout::preview(BlockLayout::HOME, self::previewBlocks($values[CustomizeTheme::BLOCKS_FIELD]));
            }
        }

        $response = $next($request);

        // Lišta náhľadu (vložená do každej šablóny, aj cudzej).
        if ($themes->previewing() !== null && ! $request->boolean('theme_customize') && str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            $content = (string) $response->getContent();
            $bar = view('themes.preview-bar', ['theme' => $themes->active()])->render();
            $response->setContent(str_replace('</body>', $bar.'</body>', $content));
        }

        return $response;
    }

    /**
     * Prepne šablónu pre pohľady aj preklady (null = aktívna šablóna).
     */
    private function usePreview(?string $name): void
    {
        $themes = app(ThemeManager::class);

        if ($themes->previewing() === $name) {
            return;
        }

        $themes->usePreview($name);
        View::replaceNamespace('theme', $themes->viewPaths());
        View::getFinder()->flush();
        app('translator')->setLoaded([]);
    }

    /**
     * Neuložený stav editora blokov na tvar uložených blokov: nahraté obrázky sú v ňom zoznamy
     * {uuid: cesta}, uložený blok má cestu (alebo zoznam ciest pri galérii).
     *
     * @param  array<int|string, mixed>  $blocks
     * @return list<array<string, mixed>>
     */
    private static function previewBlocks(array $blocks): array
    {
        $normalize = function (mixed $value) use (&$normalize): mixed {
            if (! is_array($value)) {
                return $value;
            }

            $keys = array_keys($value);
            $isUpload = $value !== [] && collect($keys)->every(fn ($key) => is_string($key) && preg_match('/^[0-9a-f-]{36}$/', $key))
                && collect($value)->every(fn ($item) => is_string($item));

            if ($isUpload) {
                return count($value) === 1 ? reset($value) : array_values($value);
            }

            return array_map($normalize, $value);
        };

        return array_values(array_filter(array_map(fn ($block) => is_array($block) ? [
            'type' => $block['type'] ?? null,
            'data' => $normalize($block['data'] ?? []),
        ] : null, $blocks)));
    }
}
