<?php

namespace Tests\Feature\Store;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Store\ProductImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_woocommerce_categories_products_prices_images_and_merchant_metadata(): void
    {
        $database = database_path('testing-woocommerce.sqlite');
        if (file_exists($database)) {
            unlink($database);
        }
        touch($database);

        Config::set('database.connections.woocommerce_test', [
            'driver' => 'sqlite',
            'database' => $database,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        $this->createWooCommerceTables('woocommerce_test', 'wp_');
        $this->seedWooCommerceCatalog('woocommerce_test', 'wp_');

        app(ProductImportService::class)->import('woocommerce_test', 'wp_');

        $this->assertDatabaseHas('product_categories', [
            'wp_term_id' => 30,
            'slug' => 'depilacao-feminina',
            'google_gender' => 'female',
        ]);
        $this->assertDatabaseHas('product_categories', [
            'wp_term_id' => 34,
            'slug' => 'avulsas',
            'google_gender' => 'female',
        ]);

        $product = Product::query()
            ->with(['categories', 'images'])
            ->where('wp_product_id', 6355)
            ->firstOrFail();

        $this->assertSame('Pernas Inteiras sessão avulsa', $product->name);
        $this->assertSame('pernas-inteiras-sessao-avulsa', $product->slug);
        $this->assertSame(25000, $product->regular_price_cents);
        $this->assertSame(25000, $product->price_cents);
        $this->assertFalse($product->merchant_is_bundle);
        $this->assertSame('approved', $product->merchant_status);
        $this->assertSame('online:pt:BR:gla_6355', $product->merchant_google_id);
        $this->assertSame('Renova Laser Depilação', $product->merchant_brand);
        $this->assertSame('new', $product->merchant_condition);
        $this->assertSame('female', $product->merchant_gender);
        $this->assertSame('adult', $product->merchant_age_group);
        $this->assertTrue($product->categories->pluck('slug')->contains('depilacao-feminina'));
        $this->assertTrue($product->categories->pluck('slug')->contains('avulsas'));
        $this->assertSame('https://example.test/pernas.png', $product->images->first()->url);
    }

    private function createWooCommerceTables(string $connection, string $prefix): void
    {
        Schema::connection($connection)->create("{$prefix}terms", function (Blueprint $table) {
            $table->unsignedBigInteger('term_id')->primary();
            $table->string('name');
            $table->string('slug');
        });

        Schema::connection($connection)->create("{$prefix}term_taxonomy", function (Blueprint $table) {
            $table->unsignedBigInteger('term_taxonomy_id')->primary();
            $table->unsignedBigInteger('term_id');
            $table->string('taxonomy');
            $table->unsignedBigInteger('parent')->default(0);
            $table->unsignedInteger('count')->default(0);
        });

        Schema::connection($connection)->create("{$prefix}term_relationships", function (Blueprint $table) {
            $table->unsignedBigInteger('object_id');
            $table->unsignedBigInteger('term_taxonomy_id');
        });

        Schema::connection($connection)->create("{$prefix}posts", function (Blueprint $table) {
            $table->unsignedBigInteger('ID')->primary();
            $table->string('post_title')->nullable();
            $table->string('post_name')->nullable();
            $table->string('post_type')->nullable();
            $table->string('post_status')->nullable();
            $table->text('post_excerpt')->nullable();
            $table->text('post_content')->nullable();
            $table->text('guid')->nullable();
        });

        Schema::connection($connection)->create("{$prefix}postmeta", function (Blueprint $table) {
            $table->id('meta_id');
            $table->unsignedBigInteger('post_id');
            $table->string('meta_key')->nullable();
            $table->text('meta_value')->nullable();
        });

        Schema::connection($connection)->create("{$prefix}gla_attribute_mapping_rules", function (Blueprint $table) {
            $table->id();
            $table->string('attribute');
            $table->string('source');
            $table->string('category_condition_type');
            $table->text('categories')->nullable();
        });
    }

    private function seedWooCommerceCatalog(string $connection, string $prefix): void
    {
        DB::connection($connection)->table("{$prefix}terms")->insert([
            ['term_id' => 30, 'name' => 'Depilação Feminina', 'slug' => 'depilacao-feminina'],
            ['term_id' => 34, 'name' => 'Sessões Avulsas', 'slug' => 'avulsas'],
        ]);

        DB::connection($connection)->table("{$prefix}term_taxonomy")->insert([
            ['term_taxonomy_id' => 300, 'term_id' => 30, 'taxonomy' => 'product_cat', 'parent' => 0, 'count' => 1],
            ['term_taxonomy_id' => 340, 'term_id' => 34, 'taxonomy' => 'product_cat', 'parent' => 30, 'count' => 1],
        ]);

        DB::connection($connection)->table("{$prefix}gla_attribute_mapping_rules")->insert([
            ['attribute' => 'brand', 'source' => 'Renova Laser Depilação', 'category_condition_type' => 'ALL', 'categories' => null],
            ['attribute' => 'condition', 'source' => 'new', 'category_condition_type' => 'ALL', 'categories' => null],
            ['attribute' => 'ageGroup', 'source' => 'adult', 'category_condition_type' => 'ALL', 'categories' => null],
            ['attribute' => 'gender', 'source' => 'female', 'category_condition_type' => 'ONLY', 'categories' => '30'],
            ['attribute' => 'isBundle', 'source' => 'yes', 'category_condition_type' => 'EXCEPT', 'categories' => '34'],
        ]);

        DB::connection($connection)->table("{$prefix}posts")->insert([
            [
                'ID' => 6355,
                'post_title' => 'Pernas Inteiras sessão avulsa',
                'post_name' => 'pernas-inteiras-sessao-avulsa',
                'post_type' => 'product',
                'post_status' => 'publish',
                'post_excerpt' => 'Sessão avulsa para pernas inteiras.',
                'post_content' => '',
                'guid' => '',
            ],
            [
                'ID' => 9100,
                'post_title' => 'Pernas',
                'post_name' => 'pernas',
                'post_type' => 'attachment',
                'post_status' => 'inherit',
                'post_excerpt' => '',
                'post_content' => '',
                'guid' => 'https://example.test/pernas.png',
            ],
        ]);

        DB::connection($connection)->table("{$prefix}term_relationships")->insert([
            ['object_id' => 6355, 'term_taxonomy_id' => 300],
            ['object_id' => 6355, 'term_taxonomy_id' => 340],
        ]);

        foreach ([
            '_regular_price' => '250',
            '_price' => '250',
            '_stock_status' => 'instock',
            '_thumbnail_id' => '9100',
            '_wc_gla_google_ids' => 'a:1:{s:2:"BR";s:21:"online:pt:BR:gla_6355";}',
            '_wc_gla_mc_status' => 'approved',
            '_wc_gla_visibility' => 'sync-and-show',
        ] as $key => $value) {
            DB::connection($connection)->table("{$prefix}postmeta")->insert([
                'post_id' => 6355,
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }
    }
}
