<?php

namespace App\Http\Middleware;

use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
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
}
