<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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

    public function create(): Response
    {
        return Inertia::render('Admin/Products/Form', [
            'product' => null,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $product = Product::query()->create($this->validatedData($request));
        $this->syncRelations($product, $request);

        return redirect()->route('admin.products.index');
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'categories']);

        return Inertia::render('Admin/Products/Form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => $product->short_description,
                'description' => $product->description,
                'sku' => $product->sku,
                'regular_price' => $this->centsToDecimal($product->regular_price_cents),
                'sale_price' => $product->sale_price_cents ? $this->centsToDecimal($product->sale_price_cents) : null,
                'price' => $this->centsToDecimal($product->price_cents),
                'stock_status' => $product->stock_status,
                'is_active' => $product->is_active,
                'is_custom_quote' => $product->is_custom_quote,
                'primary_category_id' => $product->primary_category_id,
                'category_ids' => $product->categories->pluck('id')->all(),
                'image_url' => $product->images->first()?->local_path ?: $product->images->first()?->url,
                'merchant_visibility' => $product->merchant_visibility,
                'merchant_brand' => $product->merchant_brand,
                'merchant_condition' => $product->merchant_condition,
                'merchant_age_group' => $product->merchant_age_group,
                'merchant_gender' => $product->merchant_gender,
                'merchant_color' => $product->merchant_color,
                'merchant_size' => $product->merchant_size,
                'merchant_is_bundle' => $product->merchant_is_bundle,
            ],
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validatedData($request, $product));
        $this->syncRelations($product, $request);

        return redirect()->route('admin.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->categories()->detach();
        $product->images()->delete();
        $product->delete();

        return redirect()->route('admin.products.index');
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug,'.($product?->id ?: 'NULL')],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'sku' => ['nullable', 'string', 'max:120'],
            'regular_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock_status' => ['required', 'in:instock,outofstock'],
            'is_active' => ['boolean'],
            'is_custom_quote' => ['boolean'],
            'primary_category_id' => ['nullable', 'exists:product_categories,id'],
            'merchant_visibility' => ['nullable', 'string', 'max:80'],
            'merchant_brand' => ['nullable', 'string', 'max:120'],
            'merchant_condition' => ['nullable', 'string', 'max:40'],
            'merchant_age_group' => ['nullable', 'string', 'max:40'],
            'merchant_gender' => ['nullable', 'string', 'max:40'],
            'merchant_color' => ['nullable', 'string', 'max:80'],
            'merchant_size' => ['nullable', 'string', 'max:80'],
            'merchant_is_bundle' => ['boolean'],
        ]);

        return [
            'name' => $data['name'],
            'slug' => $data['slug'] ?: Str::slug($data['name']),
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'sku' => $data['sku'] ?? null,
            'regular_price_cents' => $this->decimalToCents($data['regular_price'] ?? $data['price']),
            'sale_price_cents' => isset($data['sale_price']) && $data['sale_price'] !== null ? $this->decimalToCents($data['sale_price']) : null,
            'price_cents' => $this->decimalToCents($data['price']),
            'currency' => 'BRL',
            'stock_status' => $data['stock_status'],
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_custom_quote' => (bool) ($data['is_custom_quote'] ?? false),
            'primary_category_id' => $data['primary_category_id'] ?? null,
            'merchant_visibility' => $data['merchant_visibility'] ?? 'sync-and-show',
            'merchant_brand' => $data['merchant_brand'] ?? null,
            'merchant_condition' => $data['merchant_condition'] ?? null,
            'merchant_age_group' => $data['merchant_age_group'] ?? null,
            'merchant_gender' => $data['merchant_gender'] ?? null,
            'merchant_color' => $data['merchant_color'] ?? null,
            'merchant_size' => $data['merchant_size'] ?? null,
            'merchant_is_bundle' => (bool) ($data['merchant_is_bundle'] ?? false),
        ];
    }

    private function syncRelations(Product $product, Request $request): void
    {
        $product->categories()->sync($request->input('category_ids', []));

        if ($request->filled('image_url')) {
            $product->images()->updateOrCreate(
                ['position' => 0],
                ['url' => $request->input('image_url'), 'local_path' => null, 'alt' => $product->name],
            );
        }
    }

    private function categoryOptions()
    {
        return ProductCategory::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
    }

    private function decimalToCents(mixed $value): int
    {
        return (int) round(((float) str_replace(',', '.', (string) $value)) * 100);
    }

    private function centsToDecimal(?int $value): string
    {
        return number_format(($value ?? 0) / 100, 2, '.', '');
    }
}
