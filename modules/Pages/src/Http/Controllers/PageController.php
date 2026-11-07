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
}
