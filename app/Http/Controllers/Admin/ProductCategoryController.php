<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Inertia\Inertia;
use Inertia\Response;

class ProductCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = ProductCategory::query()
            ->withCount('products')
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(fn (ProductCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent' => $category->parent?->name,
                'google_gender' => $category->google_gender,
                'merchant_visible' => $category->merchant_visible,
                'products_count' => $category->products_count,
            ]);

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
        ]);
    }
}
