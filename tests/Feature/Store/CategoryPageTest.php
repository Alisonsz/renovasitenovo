<?php

namespace Tests\Feature\Store;

use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_feminine_category_page_groups_active_products_by_child_category(): void
    {
        $root = ProductCategory::factory()->create([
            'name' => 'Depilacao Feminina',
            'slug' => 'depilacao-feminina',
        ]);
        $child = ProductCategory::factory()->create([
            'parent_id' => $root->id,
            'name' => 'Sessoes Avulsas',
            'slug' => 'avulsas',
        ]);
        $product = Product::factory()->create([
            'primary_category_id' => $child->id,
            'name' => 'Axilas sessao avulsa',
            'slug' => 'axilas-sessao-avulsa',
            'price_cents' => 6000,
            'regular_price_cents' => 6000,
            'is_active' => true,
        ]);
        $product->categories()->attach([$root->id, $child->id]);

        $response = $this->get('/depilacao-feminina');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/Category')
            ->where('category.slug', 'depilacao-feminina')
            ->where('sections.0.slug', 'avulsas')
            ->where('sections.0.products.0.name', 'Axilas sessao avulsa')
            ->where('sections.0.products.0.price_cents', 6000)
            ->has('children', 1)
        );
    }

    public function test_masculine_category_page_does_not_list_inactive_products(): void
    {
        $root = ProductCategory::factory()->create([
            'name' => 'Depilacao Masculina',
            'slug' => 'depilacao-masculina',
        ]);
        $product = Product::factory()->create([
            'primary_category_id' => $root->id,
            'name' => 'Costas sessao avulsa',
            'slug' => 'costas-sessao-avulsa',
            'price_cents' => 28000,
            'regular_price_cents' => 28000,
            'is_active' => false,
        ]);
        $product->categories()->attach($root);

        $response = $this->get('/depilacao-masculina');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Store/Category')
            ->where('category.slug', 'depilacao-masculina')
            ->has('sections', 1)
            ->has('sections.0.products', 0)
        );
    }
}
