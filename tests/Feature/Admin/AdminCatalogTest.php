<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_products_index(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create(['name' => 'Combos', 'slug' => 'combos']);
        $product = Product::factory()->create([
            'primary_category_id' => $category->id,
            'name' => 'Combo Virilha',
            'price_cents' => 90000,
            'regular_price_cents' => 112500,
            'is_active' => true,
        ]);
        $product->categories()->attach($category);

        $this->actingAs($user)
            ->get('/admin/products')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Index')
                ->where('products.data.0.name', 'Combo Virilha')
                ->where('products.data.0.price_cents', 90000)
                ->where('products.data.0.categories.0.name', 'Combos')
            );
    }

    public function test_admin_can_view_categories_index(): void
    {
        $user = User::factory()->create();
        ProductCategory::factory()->create([
            'name' => 'Depilacao Feminina',
            'slug' => 'depilacao-feminina',
            'google_gender' => 'female',
            'merchant_visible' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/categories')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Categories/Index')
                ->where('categories.0.name', 'Depilacao Feminina')
                ->where('categories.0.slug', 'depilacao-feminina')
                ->where('categories.0.google_gender', 'female')
            );
    }
}
