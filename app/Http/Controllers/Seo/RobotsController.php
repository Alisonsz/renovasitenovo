<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function show(): Response
    {
        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /checkout',
            'Disallow: /carrinho',
            '',
            'Sitemap: '.route('seo.sitemap'),
            '',
        ]);

        return response($content, 200)->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
