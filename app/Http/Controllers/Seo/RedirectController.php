<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\SeoRedirect;
use Illuminate\Http\RedirectResponse;

class RedirectController extends Controller
{
    public function show(string $path): RedirectResponse
    {
        $source = '/'.trim($path, '/');

        $redirect = SeoRedirect::query()
            ->where('source', $source)
            ->where('is_active', true)
            ->firstOrFail();

        return redirect($redirect->target, $redirect->status_code);
    }
}
