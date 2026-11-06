<?php

namespace Modules\Storefront\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('theme::templates.home');
    }
}
