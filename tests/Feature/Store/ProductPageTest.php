<?php

namespace Tests\Feature\Store;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_page_shows_active_product_details(): void
    {
        $root = ProductCategory::factory()->create([
            'name' => 'Depilacao Feminina',
            'slug' => 'depilacao-feminina',
        ]);
        $child = ProductCategory::factory()->create([
            'parent_id' => $root->id,
            'name' => 'Combos',
            'slug' => 'combos',
        ]);
        $product = Product::factory()->create([
            'primary_category_id' => $child->id,
            'name' => 'Combo Virilha, Axila, Perianal e Buco',
            'slug' => 'combo-virilha-axila-e-perianal',
            'short_description' => 'Este combo inclui 5 sessoes de depilacao a laser.',
            'regular_price_cents' => 112500,
            'sale_price_cents' => 90000,
            'price_cents' => 90000,
            'merchant_brand' => 'Renova Laser Depilacao',
            'merchant_condition' => 'new',
            'stock_status' => 'instock',
            'is_active' => true,
        ]);
        $product->categories()->attach([$root->id, $child->id]);
        ProductImage::query()->create([
            'product_id' => $product->id,
            'url' => 'https://example.com/produto.jpg',
            'alt' => 'Produto',
            'position' => 0,
        ]);

        $response = $this->get('/produto/combo-virilha-axila-e-perianal');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/Product')
            ->where('product.name', 'Combo Virilha, Axila, Perianal e Buco')
            ->where('product.price_cents', 90000)
            ->where('product.regular_price_cents', 112500)
            ->where('product.image_url', 'https://example.com/produto.jpg')
            ->where('breadcrumbs.1.slug', 'depilacao-feminina')
            ->where('breadcrumbs.2.slug', 'combos')
            ->where('structuredData.offers.price', '900.00')
            ->where('structuredData.offers.priceCurrency', 'BRL')
        );
    }

    public function test_product_page_does_not_show_inactive_products(): void
    {
        $product = Product::factory()->create([
            'slug' => 'produto-inativo',
            'is_active' => false,
        ]);

        $response = $this->get("/produto/{$product->slug}");

        $response->assertNotFound();
    }
}
