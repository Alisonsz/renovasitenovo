<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\HtmlSanitizer;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        abort_unless($product->is_active, 404);

        $product->load(['images', 'categories.parent', 'primaryCategory.parent']);

        // Full gallery (ordered by position) for the product page carousel.
        $gallery = $product->images
            ->map(fn ($img) => [
                'url' => $img->local_path ?: $img->url,
                'alt' => $img->alt ?: $product->name,
            ])
            ->filter(fn ($img) => ! empty($img['url']))
            ->values();

        $image = $product->images->first();
        $breadcrumbs = $this->breadcrumbs($product);
        $description = strip_tags((string) ($product->short_description ?: $product->description));

        return Inertia::render('Store/Product', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'short_description' => $description,
                'description' => HtmlSanitizer::clean($product->description),
                'regular_price_cents' => $product->regular_price_cents,
                'sale_price_cents' => $product->sale_price_cents,
                'price_cents' => $product->price_cents,
                'currency' => $product->currency,
                'stock_status' => $product->stock_status,
                'is_custom_quote' => $product->is_custom_quote,
                'image_url' => $image?->local_path ?: $image?->url,
                'image_alt' => $image?->alt ?: $product->name,
                'images' => $gallery,
                'merchant_brand' => $product->merchant_brand ?: 'Renova Laser Depilação',
                'merchant_condition' => $product->merchant_condition ?: 'new',
            ],
            'breadcrumbs' => $breadcrumbs,
            'technicalSpecs' => $this->technicalSpecs($product),
            'structuredData' => $this->structuredData($product, $gallery->pluck('url')->all()),
        ]);
    }

    private function breadcrumbs(Product $product): array
    {
        $primary = $product->primaryCategory ?: $product->categories->first();
        $root = $primary?->parent ?: ($primary?->parent_id ? null : $primary);

        if ($primary?->parent) {
            $root = $primary->parent;
        }

        return collect([
            ['name' => 'Início', 'href' => '/', 'slug' => 'inicio'],
            $root ? [
                'name' => $root->name,
                'href' => $this->categoryHref($root),
                'slug' => $root->slug,
            ] : null,
            $primary && $root?->id !== $primary->id ? [
                'name' => $primary->name,
                'href' => $this->categoryHref($primary),
                'slug' => $primary->slug,
            ] : null,
            ['name' => $product->name, 'href' => null, 'slug' => $product->slug],
        ])->filter()->values()->all();
    }

    private function categoryHref(ProductCategory $category): string
    {
        return match ($category->slug) {
            'depilacao-feminina' => route('store.feminine'),
            'depilacao-masculina' => route('store.masculine'),
            default => route('store.category', ['slug' => $category->slug]),
        };
    }

    private function technicalSpecs(Product $product): array
    {
        $name = mb_strtolower($product->name);
        $areas = preg_replace('/^(combo|10 sessões de|10 sessoes de|inclui|sessão avulsa|sessao avulsa)\s+/iu', '', $product->name);
        $sessionCount = str_contains($name, 'combo') ? '05 sessões para cada área do combo' : (str_contains($name, '10 sessões') || str_contains($name, '10 sessoes') ? '10 sessões' : null);

        return collect([
            ['label' => 'Áreas', 'value' => trim($areas)],
            ['label' => 'Duração da sessão', 'value' => str_contains($name, 'perna') ? '30 a 40 minutos' : '15 a 20 minutos'],
            ['label' => 'Intervalo entre sessões', 'value' => '30 dias'],
            $sessionCount ? ['label' => 'Pacote com', 'value' => $sessionCount] : null,
        ])->filter()->values()->all();
    }

    private function structuredData(Product $product, array $imageUrls = []): array
    {
        $price = number_format(($product->sale_price_cents ?: $product->price_cents) / 100, 2, '.', '');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'description' => strip_tags((string) ($product->short_description ?: $product->description)),
            'image' => array_values($imageUrls),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->merchant_brand ?: 'Renova Laser Depilação',
            ],
            'offers' => [
                '@type' => 'Offer',
                'url' => route('store.product', ['product' => $product->slug]),
                'price' => $price,
                'priceCurrency' => $product->currency,
                'availability' => $product->stock_status === 'instock'
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];
    }
}
