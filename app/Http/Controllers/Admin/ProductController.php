<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(): Response
    {
        $products = Product::query()
            ->with(['images', 'categories'])
            ->latest('id')
            ->paginate(15)
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'price_cents' => $product->price_cents,
                'regular_price_cents' => $product->regular_price_cents,
                'sale_price_cents' => $product->sale_price_cents,
                'is_active' => $product->is_active,
                'merchant_visibility' => $product->merchant_visibility,
                'image_url' => $product->images->first()?->local_path ?: $product->images->first()?->url,
                'categories' => $product->categories->map(fn ($category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])->values(),
            ]);

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
        ]);
    }
}
