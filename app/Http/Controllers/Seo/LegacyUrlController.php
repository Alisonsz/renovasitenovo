<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Blog\BlogController;
use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\SeoRedirect;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class LegacyUrlController extends Controller
{
    public function show(string $legacyPath, BlogController $blogController): RedirectResponse|Response
    {
        $source = '/'.trim($legacyPath, '/');

        $redirect = SeoRedirect::query()
            ->where('source', $source)
            ->where('is_active', true)
            ->first();

        if ($redirect) {
            return redirect($redirect->target, $redirect->status_code);
        }

        $slug = trim($legacyPath, '/');
        $post = BlogPost::query()->where('slug', $slug)->firstOrFail();

        return $blogController->show($post);
    }
}
