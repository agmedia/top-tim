<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyCatalogRedirectController extends Controller
{
    public function __invoke(Request $request, string $destination): RedirectResponse
    {
        $destination = url($destination);

        if ($request->getQueryString()) {
            $destination .= '?' . $request->getQueryString();
        }

        return redirect()->to($destination, 301);
    }
}
