<?php

namespace Tests\Feature\Seo;

use App\Models\BlogPost;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SeoRedirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_contains_public_urls(): void
    {
        BlogPost::query()->create([
            'wp_post_id' => 1,
            'title' => 'Blog post',
            'slug' => 'blog-post',
            'status' => 'publish',
            'published_at' => now(),
        ]);

        ProductCategory::query()->create([
            'name' => 'Categoria',
            'slug' => 'categoria',
            'wp_term_id' => 10,
        ]);

        Product::query()->create([
            'name' => 'Produto',
            'slug' => 'produto-teste',
            'price_cents' => 1000,
            'regular_price_cents' => 1000,
            'currency' => 'BRL',
            'stock_status' => 'instock',
            'is_active' => true,
        ]);

        $response = $this->get('/sitemap.xml');

        $response
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/blog-post', false)
            ->assertSee('/produto/produto-teste', false)
            ->assertSee('/loja/categoria/categoria', false);
    }

    public function test_robots_points_to_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('User-agent: *')
            ->assertSee('/sitemap.xml');
    }

    public function test_legacy_url_redirects_before_trying_blog_slug(): void
    {
        SeoRedirect::query()->create([
            'source' => '/depilacao-femina',
            'target' => '/depilacao-feminina',
            'status_code' => 301,
            'is_active' => true,
        ]);

        $this->get('/depilacao-femina')
            ->assertRedirect('/depilacao-feminina')
            ->assertStatus(301);
    }
}
