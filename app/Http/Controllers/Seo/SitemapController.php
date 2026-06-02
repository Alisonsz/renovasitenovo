<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function show(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'lastmod' => now()],
            ['loc' => route('quem-somos'), 'lastmod' => now()],
            ['loc' => route('nossa-tecnologia'), 'lastmod' => now()],
            ['loc' => route('blog.index'), 'lastmod' => BlogPost::query()->max('modified_at') ?: now()],
            ['loc' => route('store.feminine'), 'lastmod' => Product::query()->max('updated_at') ?: now()],
            ['loc' => route('store.masculine'), 'lastmod' => Product::query()->max('updated_at') ?: now()],
        ]);

        ProductCategory::query()
            ->whereNotIn('slug', ['depilacao-feminina', 'depilacao-masculina'])
            ->get()
            ->each(fn (ProductCategory $category) => $urls->push([
                'loc' => route('store.category', ['slug' => $category->slug]),
                'lastmod' => $category->updated_at ?: now(),
            ]));

        Product::query()
            ->where('is_active', true)
            ->get()
            ->each(fn (Product $product) => $urls->push([
                'loc' => route('store.product', ['product' => $product->slug]),
                'lastmod' => $product->updated_at ?: now(),
            ]));

        BlogPost::query()
            ->published()
            ->get()
            ->each(fn (BlogPost $post) => $urls->push([
                'loc' => url('/'.$post->slug),
                'lastmod' => $post->modified_at ?: $post->updated_at ?: now(),
            ]));

        $xml = view('seo.sitemap', ['urls' => $urls])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
