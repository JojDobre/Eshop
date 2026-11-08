<?php

namespace Modules\Pages\Http\Controllers;

use App\Core\Content\AlternateUrls;
use App\Core\Content\ShopLocales;
use App\Core\Themes\ThemeManager;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Pages\Models\Page;

class PageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = Page::query()->visible()->whereSlug($slug)->firstOrFail();
        app(AlternateUrls::class)->using(fn () => $page->url());

        return view(app(ThemeManager::class)->template('page', ['page-'.$page->slugIn(app(ShopLocales::class)->default())]), ['page' => $page]);
    }

    /**
     * Archívna verzia právnej stránky (odkaz z objednávky: „súhlasili ste s verziou 3“).
     */
    public function version(string $slug, int $version): View
    {
        $page = Page::query()->whereSlug($slug)->firstOrFail();
        $archived = $page->versions()->where('version', $version)->firstOrFail();

        return view('theme::templates.page', ['page' => $page, 'version' => $archived]);
    }
}
