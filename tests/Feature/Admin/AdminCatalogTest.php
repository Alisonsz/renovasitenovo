<?php

namespace Tests\Feature\Admin;

use App\Models\BlogPost;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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

    public function test_admin_can_save_product_image_gallery(): void
    {
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create(['name' => 'Pacotes', 'slug' => 'pacotes']);

        $this->actingAs($user)->post('/admin/products', [
            'name' => 'Produto Galeria',
            'slug' => 'produto-galeria',
            'price' => '100.00',
            'stock_status' => 'instock',
            'is_active' => true,
            'category_ids' => [$category->id],
            'image_urls' => [
                'https://cdn.example.com/a.png',
                '   ', // blank rows are ignored
                'https://cdn.example.com/b.png',
                'https://cdn.example.com/c.png',
            ],
        ])->assertRedirect('/admin/products');

        $product = Product::query()->where('slug', 'produto-galeria')->firstOrFail();
        $images = $product->images()->orderBy('position')->get();

        $this->assertCount(3, $images);
        $this->assertSame('https://cdn.example.com/a.png', $images[0]->url);
        $this->assertSame(0, $images[0]->position);
        $this->assertSame('https://cdn.example.com/b.png', $images[1]->url);
        $this->assertSame(1, $images[1]->position);
        $this->assertSame('https://cdn.example.com/c.png', $images[2]->url);
        $this->assertSame(2, $images[2]->position);
    }

    public function test_admin_update_replaces_gallery_and_edit_exposes_image_urls(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Combo X', 'slug' => 'combo-x', 'is_active' => true]);
        $product->images()->createMany([
            ['url' => 'https://cdn.example.com/old1.png', 'position' => 0, 'alt' => 'Combo X'],
            ['url' => 'https://cdn.example.com/old2.png', 'position' => 1, 'alt' => 'Combo X'],
        ]);

        // Edit page exposes the existing gallery as image_urls.
        $this->actingAs($user)
            ->get("/admin/products/{$product->id}/edit")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Products/Form')
                ->where('product.image_urls', [
                    'https://cdn.example.com/old1.png',
                    'https://cdn.example.com/old2.png',
                ])
            );

        // Updating with a new gallery replaces the old images entirely.
        $this->actingAs($user)->put("/admin/products/{$product->id}", [
            'name' => 'Combo X',
            'slug' => 'combo-x',
            'price' => '100.00',
            'stock_status' => 'instock',
            'is_active' => true,
            'image_urls' => ['https://cdn.example.com/new1.png'],
        ])->assertRedirect('/admin/products');

        $images = $product->fresh()->images()->orderBy('position')->get();
        $this->assertCount(1, $images);
        $this->assertSame('https://cdn.example.com/new1.png', $images[0]->url);
    }

    public function test_admin_can_upload_product_gallery_images(): void
    {
        Storage::fake('uploads', ['url' => '/uploads']);
        $user = User::factory()->create();
        $category = ProductCategory::factory()->create(['name' => 'Pacotes', 'slug' => 'pacotes']);

        $this->actingAs($user)->post('/admin/products', [
            'name' => 'Produto Upload',
            'slug' => 'produto-upload',
            'price' => '100.00',
            'stock_status' => 'instock',
            'is_active' => 1,
            'gallery_set' => 1,
            'image_order' => ['n0', 'n1'],
            'new_images' => [
                UploadedFile::fake()->image('a.jpg', 800, 600),
                UploadedFile::fake()->image('b.jpg', 800, 600),
            ],
            'category_ids' => [$category->id],
        ])->assertRedirect('/admin/products');

        $product = Product::query()->where('slug', 'produto-upload')->firstOrFail();
        $images = $product->images()->orderBy('position')->get();

        $this->assertCount(2, $images);
        foreach ($images as $position => $image) {
            $this->assertSame($position, $image->position);
            // Served directly from public/uploads (no symlink needed).
            $this->assertStringStartsWith('/uploads/products/', (string) $image->local_path);
            Storage::disk('uploads')->assertExists(Str::after($image->local_path, '/uploads/'));
        }
    }

    public function test_admin_update_gallery_reorders_keeps_and_deletes_files(): void
    {
        Storage::fake('uploads', ['url' => '/uploads']);
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Combo Y', 'slug' => 'combo-y', 'is_active' => true]);

        Storage::disk('uploads')->put('products/old1.webp', 'x');
        Storage::disk('uploads')->put('products/old2.webp', 'y');
        $img1 = $product->images()->create(['url' => '/uploads/products/old1.webp', 'local_path' => '/uploads/products/old1.webp', 'position' => 0, 'alt' => 'a']);
        $img2 = $product->images()->create(['url' => '/uploads/products/old2.webp', 'local_path' => '/uploads/products/old2.webp', 'position' => 1, 'alt' => 'b']);

        // Keep img2, drop img1, add a new upload — final order: new first, then img2.
        $this->actingAs($user)->post("/admin/products/{$product->id}", [
            '_method' => 'put',
            'name' => 'Combo Y',
            'slug' => 'combo-y',
            'price' => '100.00',
            'stock_status' => 'instock',
            'is_active' => 1,
            'gallery_set' => 1,
            'image_order' => ['n0', "e{$img2->id}"],
            'new_images' => [UploadedFile::fake()->image('new.jpg')],
        ])->assertRedirect('/admin/products');

        $images = $product->fresh()->images()->orderBy('position')->get();
        $this->assertCount(2, $images);
        $this->assertStringStartsWith('/uploads/products/', (string) $images[0]->local_path);
        $this->assertNotSame($img1->id, $images[0]->id);
        $this->assertSame($img2->id, $images[1]->id);

        // Removed image's file deleted; kept image's file preserved.
        Storage::disk('uploads')->assertMissing('products/old1.webp');
        Storage::disk('uploads')->assertExists('products/old2.webp');
        $this->assertDatabaseMissing('product_images', ['id' => $img1->id]);
    }

    public function test_admin_can_clear_gallery_when_empty(): void
    {
        Storage::fake('uploads', ['url' => '/uploads']);
        $user = User::factory()->create();
        $product = Product::factory()->create(['slug' => 'combo-z', 'is_active' => true]);
        Storage::disk('uploads')->put('products/x.webp', 'x');
        $product->images()->create(['url' => '/uploads/products/x.webp', 'local_path' => '/uploads/products/x.webp', 'position' => 0, 'alt' => 'a']);

        $this->actingAs($user)->post("/admin/products/{$product->id}", [
            '_method' => 'put',
            'name' => $product->name,
            'slug' => 'combo-z',
            'price' => '100.00',
            'stock_status' => 'instock',
            'is_active' => 1,
            'gallery_set' => 1,
            // no image_order, no new_images => gallery cleared
        ])->assertRedirect('/admin/products');

        $this->assertSame(0, $product->fresh()->images()->count());
        Storage::disk('uploads')->assertMissing('products/x.webp');
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
