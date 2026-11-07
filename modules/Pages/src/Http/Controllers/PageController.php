<?php

namespace Modules\Pages\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Pages\Models\Page;

class PageController extends Controller
{
    public function __invoke(string $slug): View
    {
        return view('theme::templates.page', [
            'page' => Page::query()->visible()->whereSlug($slug)->firstOrFail(),
        ]);
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
