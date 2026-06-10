<?php

namespace Tests\Feature\Console;

use App\Models\BlogPost;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportWordpressImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_copies_wp_images_and_rewrites_db(): void
    {
        Storage::fake('uploads', ['url' => '/uploads']);

        // Fake local WordPress uploads folder with a couple of files.
        $wp = storage_path('framework/testing/wp-uploads');
        File::ensureDirectoryExists($wp.'/2025/06');
        File::ensureDirectoryExists($wp.'/2026/03');
        File::put($wp.'/2025/06/x.png', 'product-image-bytes');
        File::put($wp.'/2026/03/cover.jpeg', 'blog-image-bytes');

        $product = Product::factory()->create();
        $img = $product->images()->create([
            'url' => 'https://renovalaserdepilacao.com.br/wp-content/uploads/2025/06/x.png',
            'local_path' => null,
            'position' => 0,
            'alt' => 'x',
        ]);

        $post = BlogPost::create([
            'wp_post_id' => 99999,
            'title' => 'Post',
            'slug' => 'post',
            'status' => 'publish',
            'is_indexable' => true,
            // relative URL (the case my first regex missed)
            'content_html' => '<p>oi</p><img src="/wp-content/uploads/2026/03/cover.jpeg">',
        ]);

        $this->artisan('images:import-wp', ['--wp-uploads' => $wp])->assertExitCode(0);

        $img->refresh();
        $post->refresh();

        // Product image is now local.
        $this->assertSame('/uploads/wp/2025/06/x.png', $img->local_path);
        Storage::disk('uploads')->assertExists('wp/2025/06/x.png');

        // Blog HTML rewritten, file copied, no more /wp-content/.
        $this->assertStringContainsString('/uploads/wp/2026/03/cover.jpeg', $post->content_html);
        $this->assertStringNotContainsString('/wp-content/uploads/', $post->content_html);
        Storage::disk('uploads')->assertExists('wp/2026/03/cover.jpeg');

        File::deleteDirectory($wp);
    }
}
