<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function create(): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => null,
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ProductCategory::query()->create($this->validatedData($request));

        return redirect()->route('admin.categories.index');
    }

    public function edit(ProductCategory $productCategory): Response
    {
        return Inertia::render('Admin/Categories/Form', [
            'category' => [
                'id' => $productCategory->id,
                'name' => $productCategory->name,
                'slug' => $productCategory->slug,
                'description' => $productCategory->description,
                'parent_id' => $productCategory->parent_id,
                'google_gender' => $productCategory->google_gender,
                'merchant_visible' => $productCategory->merchant_visible,
                'position' => $productCategory->position,
            ],
            'parents' => $this->parentOptions($productCategory->id),
        ]);
    }

    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $productCategory->update($this->validatedData($request, $productCategory));

        return redirect()->route('admin.categories.index');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        if ($productCategory->products()->exists() || $productCategory->children()->exists()) {
            return back()->withErrors(['category' => 'Remova produtos e subcategorias antes de excluir.']);
        }

        $productCategory->delete();

        return redirect()->route('admin.categories.index');
    }

    private function validatedData(Request $request, ?ProductCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:product_categories,slug,'.($category?->id ?: 'NULL')],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:product_categories,id'],
            'google_gender' => ['nullable', 'string', 'max:40'],
            'merchant_visible' => ['boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'google_gender' => $data['google_gender'] ?? null,
            'merchant_visible' => (bool) ($data['merchant_visible'] ?? false),
            'position' => (int) ($data['position'] ?? 0),
        ];
    }

    private function parentOptions(?int $exceptId = null)
    {
        return ProductCategory::query()
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }
}
