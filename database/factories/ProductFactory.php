<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);

        return [
            'wp_product_id' => null,
            'primary_category_id' => ProductCategory::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'short_description' => fake()->sentence(),
            'description' => null,
            'sku' => null,
            'regular_price_cents' => 10000,
            'sale_price_cents' => null,
            'price_cents' => 10000,
            'currency' => 'BRL',
            'stock_status' => 'instock',
            'is_active' => true,
            'is_custom_quote' => false,
            'merchant_visibility' => 'sync-and-show',
            'merchant_status' => null,
            'merchant_google_id' => null,
            'merchant_brand' => null,
            'merchant_condition' => null,
            'merchant_age_group' => null,
            'merchant_gender' => null,
            'merchant_color' => null,
            'merchant_size' => null,
            'merchant_is_bundle' => false,
            'metadata' => null,
        ];
    }
}
