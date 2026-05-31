<?php

namespace App\Services\Store;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductImportService
{
    /** @return array{categories:int, products:int} */
    public function import(string $connection = 'mysql', string $prefix = 'wpk7_'): array
    {
        $rules = $this->merchantRules($connection, $prefix);
        $categories = $this->importCategories($connection, $prefix, $rules);
        $products = $this->importProducts($connection, $prefix, $rules);

        return [
            'categories' => $categories,
            'products' => $products,
        ];
    }

    private function importCategories(string $connection, string $prefix, array $rules): int
    {
        $rows = DB::connection($connection)
            ->table("{$prefix}terms as t")
            ->join("{$prefix}term_taxonomy as tt", 'tt.term_id', '=', 't.term_id')
            ->where('tt.taxonomy', 'product_cat')
            ->orderBy('tt.parent')
            ->orderBy('t.name')
            ->get([
                't.term_id',
                't.name',
                't.slug',
                'tt.parent',
                'tt.count',
            ]);

        $imported = 0;

        foreach ($rows as $row) {
            $parent = $row->parent
                ? ProductCategory::query()->where('wp_term_id', $row->parent)->first()
                : null;
            $termIds = $parent ? [$parent->wp_term_id, $row->term_id] : [$row->term_id];

            ProductCategory::query()->updateOrCreate(
                ['wp_term_id' => $row->term_id],
                [
                    'parent_id' => $parent?->id,
                    'name' => $this->normalizeText($row->name),
                    'slug' => $row->slug,
                    'google_gender' => $this->ruleFor('gender', $termIds, $rules),
                    'merchant_visible' => true,
                    'position' => (int) $row->term_id,
                ],
            );

            $imported++;
        }

        return $imported;
    }

    private function importProducts(string $connection, string $prefix, array $rules): int
    {
        $rows = DB::connection($connection)
            ->table("{$prefix}posts")
            ->where('post_type', 'product')
            ->where('post_status', 'publish')
            ->orderBy('ID')
            ->get();

        $imported = 0;

        foreach ($rows as $row) {
            $meta = $this->postMeta($connection, $prefix, $row->ID);
            $categories = $this->productCategories($connection, $prefix, $row->ID);
            $primaryCategory = $categories->sortByDesc(fn (ProductCategory $category) => $category->parent_id !== null)->first();
            $termIds = $categories->pluck('wp_term_id')->filter()->values()->all();
            $googleIds = $this->parsePhpSerializedArray($meta['_wc_gla_google_ids'] ?? null);
            $googleId = is_array($googleIds) ? ($googleIds['BR'] ?? reset($googleIds) ?: null) : null;
            $regularPrice = $this->priceToCents($meta['_regular_price'] ?? $meta['_price'] ?? 0);
            $salePrice = isset($meta['_sale_price']) && $meta['_sale_price'] !== ''
                ? $this->priceToCents($meta['_sale_price'])
                : null;
            $price = $this->priceToCents($meta['_price'] ?? $meta['_sale_price'] ?? $meta['_regular_price'] ?? 0);

            $product = Product::query()->updateOrCreate(
                ['wp_product_id' => $row->ID],
                [
                    'primary_category_id' => $primaryCategory?->id,
                    'name' => $this->normalizeText($row->post_title),
                    'slug' => $row->post_name ?: Str::slug($row->post_title),
                    'short_description' => $this->normalizeText($row->post_excerpt),
                    'description' => $this->normalizeText($row->post_content),
                    'sku' => $meta['_sku'] ?? null,
                    'regular_price_cents' => $regularPrice,
                    'sale_price_cents' => $salePrice,
                    'price_cents' => $price,
                    'currency' => 'BRL',
                    'stock_status' => $meta['_stock_status'] ?? 'instock',
                    'is_active' => true,
                    'is_custom_quote' => $price === 0,
                    'merchant_visibility' => $meta['_wc_gla_visibility'] ?? 'sync-and-show',
                    'merchant_status' => $meta['_wc_gla_mc_status'] ?? null,
                    'merchant_google_id' => $googleId,
                    'merchant_brand' => $meta['_wc_gla_brand'] ?? $this->ruleFor('brand', $termIds, $rules),
                    'merchant_condition' => $meta['_wc_gla_condition'] ?? $this->ruleFor('condition', $termIds, $rules),
                    'merchant_age_group' => $meta['_wc_gla_ageGroup'] ?? $this->ruleFor('ageGroup', $termIds, $rules),
                    'merchant_gender' => $meta['_wc_gla_gender'] ?? $this->ruleFor('gender', $termIds, $rules),
                    'merchant_color' => $meta['_wc_gla_color'] ?? $this->ruleFor('color', $termIds, $rules),
                    'merchant_size' => $meta['_wc_gla_size'] ?? $this->ruleFor('size', $termIds, $rules),
                    'merchant_is_bundle' => (bool) $this->ruleFor('isBundle', $termIds, $rules, false),
                    'metadata' => [
                        'woocommerce_meta' => $meta,
                    ],
                ],
            );

            $product->categories()->sync($categories->pluck('id')->all());
            $this->syncPrimaryImage($connection, $prefix, $product, $meta);

            $imported++;
        }

        return $imported;
    }

    private function postMeta(string $connection, string $prefix, int $postId): array
    {
        return DB::connection($connection)
            ->table("{$prefix}postmeta")
            ->where('post_id', $postId)
            ->get(['meta_key', 'meta_value'])
            ->filter(fn ($row) => $row->meta_key !== null)
            ->mapWithKeys(fn ($row) => [$row->meta_key => $row->meta_value])
            ->all();
    }

    /** @return Collection<int, ProductCategory> */
    private function productCategories(string $connection, string $prefix, int $productId): Collection
    {
        $termIds = DB::connection($connection)
            ->table("{$prefix}term_relationships as tr")
            ->join("{$prefix}term_taxonomy as tt", 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $productId)
            ->where('tt.taxonomy', 'product_cat')
            ->pluck('tt.term_id');

        return ProductCategory::query()
            ->whereIn('wp_term_id', $termIds->all())
            ->get();
    }

    private function syncPrimaryImage(string $connection, string $prefix, Product $product, array $meta): void
    {
        $attachmentId = (int) ($meta['_thumbnail_id'] ?? 0);
        if ($attachmentId <= 0) {
            return;
        }

        $attachment = DB::connection($connection)
            ->table("{$prefix}posts")
            ->where('ID', $attachmentId)
            ->first(['ID', 'post_title', 'guid']);

        if (! $attachment || ! $attachment->guid) {
            return;
        }

        $product->images()->updateOrCreate(
            ['wp_attachment_id' => $attachment->ID],
            [
                'url' => $attachment->guid,
                'alt' => $this->normalizeText($attachment->post_title),
                'position' => 0,
            ],
        );
    }

    private function merchantRules(string $connection, string $prefix): array
    {
        if (! DB::connection($connection)->getSchemaBuilder()->hasTable("{$prefix}gla_attribute_mapping_rules")) {
            return [];
        }

        return DB::connection($connection)
            ->table("{$prefix}gla_attribute_mapping_rules")
            ->get()
            ->groupBy('attribute')
            ->all();
    }

    private function ruleFor(string $attribute, array $termIds, array $rules, mixed $default = null): mixed
    {
        foreach ($rules[$attribute] ?? [] as $rule) {
            $categoryIds = collect(explode(',', (string) $rule->categories))
                ->filter()
                ->map(fn ($id) => (int) trim($id))
                ->all();

            if ($rule->category_condition_type === 'ALL') {
                return $this->merchantValue($rule->source, $attribute);
            }

            if ($rule->category_condition_type === 'ONLY' && count(array_intersect($termIds, $categoryIds)) > 0) {
                return $this->merchantValue($rule->source, $attribute);
            }

            if ($rule->category_condition_type === 'EXCEPT' && count(array_intersect($termIds, $categoryIds)) === 0) {
                return $this->merchantValue($rule->source, $attribute);
            }
        }

        return $default;
    }

    private function merchantValue(string $value, string $attribute): mixed
    {
        if ($attribute === 'isBundle') {
            return $value === 'yes';
        }

        return $this->normalizeText($value);
    }

    private function priceToCents(string|int|float|null $price): int
    {
        $normalized = str_replace(',', '.', (string) ($price ?? 0));

        return (int) round(((float) $normalized) * 100);
    }

    private function parsePhpSerializedArray(?string $value): mixed
    {
        if (! $value) {
            return null;
        }

        $parsed = @unserialize($value);

        return $parsed === false && $value !== serialize(false) ? null : $parsed;
    }

    private function normalizeText(?string $value): ?string
    {
        return $value === null ? null : html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
