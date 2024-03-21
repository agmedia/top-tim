<?php

namespace App\Http\Controllers;

use App\Models\Front\Page;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Lang;

class FrontBaseController extends Controller
{

    public function __construct()
    {
        $pages = Page::with('translation')->get();
        View::share('pages', $pages);

        $js_lang = json_encode(Lang::get('front/cart'));
        View::share('js_lang', $js_lang);
    }

}
