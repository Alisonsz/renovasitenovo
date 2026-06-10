<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
                'stock_quantity' => $product->stock_quantity,
                'manage_stock' => $product->manage_stock,
                'is_active' => $product->is_active,
                'is_custom_quote' => $product->is_custom_quote,
                'is_treatment' => $product->is_treatment,
                'sessions_count' => $product->sessions_count,
                'session_duration_min' => $product->session_duration_min,
                'primary_category_id' => $product->primary_category_id,
                'category_ids' => $product->categories->pluck('id')->all(),
                'image_url' => $product->images->first()?->local_path ?: $product->images->first()?->url,
                'image_urls' => $product->images
                    ->map(fn ($img) => $img->local_path ?: $img->url)
                    ->filter()
                    ->values()
                    ->all(),
                // Gallery items for the upload manager: id + display URL, ordered.
                'gallery' => $product->images
                    ->sortBy('position')
                    ->map(fn ($img) => ['id' => $img->id, 'url' => $img->local_path ?: $img->url])
                    ->values()
                    ->all(),
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
        $product->images->each(fn (ProductImage $image) => $this->deleteImageFile($image));
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
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'manage_stock' => ['boolean'],
            'is_active' => ['boolean'],
            'is_custom_quote' => ['boolean'],
            'is_treatment' => ['boolean'],
            'sessions_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'session_duration_min' => ['nullable', 'integer', 'min:15', 'max:240'],
            'primary_category_id' => ['nullable', 'exists:product_categories,id'],
            'merchant_visibility' => ['nullable', 'string', 'max:80'],
            'merchant_brand' => ['nullable', 'string', 'max:120'],
            'merchant_condition' => ['nullable', 'string', 'max:40'],
            'merchant_age_group' => ['nullable', 'string', 'max:40'],
            'merchant_gender' => ['nullable', 'string', 'max:40'],
            'merchant_color' => ['nullable', 'string', 'max:80'],
            'merchant_size' => ['nullable', 'string', 'max:80'],
            'merchant_is_bundle' => ['boolean'],
            // Gallery uploads (handled in syncGallery).
            'gallery_set' => ['nullable'],
            'image_order' => ['nullable', 'array'],
            'image_order.*' => ['string', 'max:40'],
            'new_images' => ['nullable', 'array', 'max:30'],
            'new_images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:10240'],
        ]);

        return [
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? null) ?: Str::slug($data['name']),
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'sku' => $data['sku'] ?? null,
            'regular_price_cents' => $this->decimalToCents($data['regular_price'] ?? $data['price']),
            'sale_price_cents' => isset($data['sale_price']) && $data['sale_price'] !== null ? $this->decimalToCents($data['sale_price']) : null,
            'price_cents' => $this->decimalToCents($data['price']),
            'currency' => 'BRL',
            'stock_status' => $data['stock_status'],
            'stock_quantity' => $data['stock_quantity'] ?? null,
            'manage_stock' => (bool) ($data['manage_stock'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'is_custom_quote' => (bool) ($data['is_custom_quote'] ?? false),
            'is_treatment' => (bool) ($data['is_treatment'] ?? false),
            'sessions_count' => ($data['is_treatment'] ?? false) ? ($data['sessions_count'] ?? null) : null,
            'session_duration_min' => $data['session_duration_min'] ?? 30,
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

        // New upload-based gallery manager: authoritative when the form sends
        // the gallery_set marker (it always does, even when emptied).
        if ($request->boolean('gallery_set')) {
            $this->syncGallery($product, $request);

            return;
        }

        // Legacy path: a list of image URLs (ordered), or the single image_url
        // field. Kept for the data import/export flow and API callers.
        $urls = collect($request->input('image_urls', []))
            ->map(fn ($u) => is_string($u) ? trim($u) : '')
            ->filter()
            ->values();

        if ($urls->isEmpty() && $request->filled('image_url')) {
            $urls = collect([trim((string) $request->input('image_url'))]);
        }

        if (! $request->has('image_urls') && ! $request->filled('image_url')) {
            return;
        }

        $product->images()->delete();
        $urls->each(function ($url, $i) use ($product) {
            $product->images()->create([
                'url' => $url,
                'local_path' => null,
                'alt' => $product->name,
                'position' => $i,
            ]);
        });
    }

    /**
     * Rebuild the product gallery from uploaded files + an ordering array.
     *
     * - new_images[]  : freshly uploaded files (stored on the public disk)
     * - image_order[] : final order as tokens — "e<id>" (keep existing image)
     *                   or "n<index>" (a file from new_images by its index)
     */
    private function syncGallery(Product $product, Request $request): void
    {
        $order = collect($request->input('image_order', []))
            ->filter(fn ($t) => is_string($t) && $t !== '')
            ->values();

        // Store uploaded files first, mapping new-index => created image id.
        $files = $request->file('new_images', []);
        $files = is_array($files) ? $files : array_filter([$files]);

        $newIds = [];
        foreach ($files as $index => $file) {
            if (! $file || ! $file->isValid()) {
                continue;
            }

            // Stored inside public/uploads and served directly (no symlink) —
            // works on shared hosting where storage:link isn't available.
            $path = $file->store('products', 'uploads');
            $web = Storage::disk('uploads')->url($path);

            $image = $product->images()->create([
                'url' => $web,
                'local_path' => $web,
                'alt' => $product->name,
                'position' => 10000 + (int) $index,
            ]);

            $newIds[(string) $index] = $image->id;
        }

        // Existing images the user chose to keep.
        $keptExistingIds = $order
            ->filter(fn ($t) => str_starts_with($t, 'e'))
            ->map(fn ($t) => (int) substr($t, 1))
            ->filter()
            ->all();

        // Delete images that are no longer present (and clean their files).
        $product->images()
            ->whereNotIn('id', array_merge($keptExistingIds, array_values($newIds)))
            ->get()
            ->each(function (ProductImage $image) {
                $this->deleteImageFile($image);
                $image->delete();
            });

        // Apply final positions following the requested order.
        $position = 0;
        foreach ($order as $token) {
            $id = null;
            if (str_starts_with($token, 'e')) {
                $id = (int) substr($token, 1);
            } elseif (str_starts_with($token, 'n')) {
                $id = $newIds[substr($token, 1)] ?? null;
            }

            if ($id) {
                $product->images()->whereKey($id)->update(['position' => $position++]);
            }
        }
    }

    /**
     * Remove an image's file from disk, but only when it's one of ours
     * (never touch externally imported WordPress URLs).
     */
    private function deleteImageFile(ProductImage $image): void
    {
        $path = (string) $image->local_path;

        if (str_contains($path, '/uploads/')) {
            Storage::disk('uploads')->delete(Str::after($path, '/uploads/'));
        } elseif (str_starts_with($path, '/storage/')) {
            // Legacy: uploads feitos antes (na storage/app/public via symlink).
            Storage::disk('public')->delete(substr($path, strlen('/storage/')));
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
