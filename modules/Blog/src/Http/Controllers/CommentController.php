<?php

namespace Modules\Blog\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Modules\Blog\BlogSettings;
use Modules\Blog\Models\Post;
use Throwable;

/**
 * Komentár k článku s ochranou proti spamu: skryté pole (honeypot), minimálny čas vyplnenia
 * a obmedzenie počtu odoslaní. Podľa nastavenia sa zobrazí až po schválení.
 */
class CommentController extends Controller
{
    /** Najkratší čas medzi zobrazením formulára a odoslaním (sekundy). */
    public const MIN_SECONDS = 3;

    public function __invoke(Request $request, string $slug): RedirectResponse
    {
        $post = Post::query()->published()->whereSlug($slug)->firstOrFail();
        $settings = app(BlogSettings::class);
        abort_unless($post->comments_enabled && $settings->comments_enabled, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:255'],
            'content' => ['required', 'string', 'min:3', 'max:3000'],
        ]);

        // Roboty: vyplnené skryté pole alebo príliš rýchle odoslanie – tvárime sa, že je všetko v poriadku.
        if (filled($request->input('website')) || ! $this->humanTiming((string) $request->input('form_token'))) {
            return redirect()->to($post->url().'#komentare')->with('comment_status', __('theme::blog.comment_pending'));
        }

        $post->comments()->create([
            ...$data,
            'is_approved' => ! $settings->comments_require_approval,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->to($post->url().'#komentare')
            ->with('comment_status', __($settings->comments_require_approval ? 'theme::blog.comment_pending' : 'theme::blog.comment_published'));
    }

    private function humanTiming(string $token): bool
    {
        try {
            return time() - (int) Crypt::decryptString($token) >= self::MIN_SECONDS;
        } catch (Throwable) {
            return false;
        }
    }
}
