<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\BlogPost;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
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

    public function test_admin_can_create_product_category_and_blog_post(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create(['name' => 'Pacotes', 'slug' => 'pacotes']);

        $this->actingAs($user)->post('/admin/products', [
            'name' => 'Produto Admin',
            'slug' => 'produto-admin',
            'price' => '120.00',
            'regular_price' => '150.00',
            'stock_status' => 'instock',
            'is_active' => true,
            'is_custom_quote' => false,
            'merchant_visibility' => 'sync-and-show',
            'merchant_brand' => 'Renova Laser',
            'merchant_condition' => 'new',
            'merchant_age_group' => 'adult',
            'merchant_gender' => 'female',
            'merchant_is_bundle' => true,
            'category_ids' => [$category->id],
        ])->assertRedirect('/admin/products');

        $this->assertDatabaseHas('products', [
            'name' => 'Produto Admin',
            'slug' => 'produto-admin',
            'price_cents' => 12000,
            'merchant_brand' => 'Renova Laser',
            'merchant_gender' => 'female',
            'merchant_is_bundle' => true,
        ]);

        $this->actingAs($user)->post('/admin/categories', [
            'name' => 'Nova Categoria',
            'slug' => 'nova-categoria',
            'merchant_visible' => true,
        ])->assertRedirect('/admin/categories');

        $this->assertDatabaseHas('product_categories', [
            'name' => 'Nova Categoria',
            'slug' => 'nova-categoria',
        ]);

        $this->actingAs($user)->post('/admin/blog-posts', [
            'title' => 'Post Admin',
            'slug' => 'post-admin',
            'status' => 'publish',
            'is_indexable' => true,
        ])->assertRedirect('/admin/blog-posts');

        $this->assertDatabaseHas('blog_posts', [
            'title' => 'Post Admin',
            'slug' => 'post-admin',
            'status' => 'publish',
        ]);
    }

    public function test_admin_can_view_orders_and_create_coupon(): void
    {
        $user = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Maria Silva',
            'email' => 'maria@example.com',
            'phone' => '11999999999',
            'document' => '12345678909',
        ]);
        Order::query()->create([
            'number' => 'RL-20260601-000001',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'payment_method' => 'pagbank_checkout',
            'subtotal_cents' => 90000,
            'total_cents' => 90000,
        ]);

        $this->actingAs($user)
            ->get('/admin/orders')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Orders/Index')
                ->where('orders.data.0.number', 'RL-20260601-000001')
                ->where('orders.data.0.customer.email', 'maria@example.com')
            );

        $this->actingAs($user)->post('/admin/coupons', [
            'code' => 'RENOVA20',
            'type' => 'fixed_cart',
            'amount' => '20.00',
            'is_active' => true,
        ])->assertRedirect('/admin/coupons');

        $this->assertDatabaseHas('coupons', [
            'code' => 'renova20',
            'type' => 'fixed_cart',
            'amount_cents' => 2000,
        ]);

        $this->assertSame(1, Coupon::query()->count());
        $this->assertSame(0, BlogPost::query()->count());
    }
}
