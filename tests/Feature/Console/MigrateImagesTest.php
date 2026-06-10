<?php

namespace Tests\Feature\Console;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MigrateImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_moves_storage_images_to_uploads_and_leaves_external_urls(): void
    {
        Storage::fake('public');
        Storage::fake('uploads', ['url' => '/uploads']);

        $product = Product::factory()->create();
        Storage::disk('public')->put('products/old.webp', 'data');

        $uploaded = $product->images()->create([
            'url' => '/storage/products/old.webp',
            'local_path' => '/storage/products/old.webp',
            'position' => 0,
            'alt' => 'x',
        ]);
        $external = $product->images()->create([
            'url' => 'https://renovalaserdepilacao.com.br/wp-content/uploads/2025/06/img.png',
            'local_path' => null,
            'position' => 1,
            'alt' => 'y',
        ]);

        $this->artisan('images:migrate-to-uploads')->assertExitCode(0);

        $uploaded->refresh();
        $external->refresh();

        // Migrated file + DB pointers now use /uploads.
        $this->assertSame('/uploads/products/old.webp', $uploaded->local_path);
        $this->assertSame('/uploads/products/old.webp', $uploaded->url);
        Storage::disk('uploads')->assertExists('products/old.webp');

        // External (imported) image untouched.
        $this->assertSame('https://renovalaserdepilacao.com.br/wp-content/uploads/2025/06/img.png', $external->url);
        $this->assertNull($external->local_path);
    }

    public function test_dry_run_changes_nothing(): void
    {
        Storage::fake('public');
        Storage::fake('uploads', ['url' => '/uploads']);

        $product = Product::factory()->create();
        Storage::disk('public')->put('products/old.webp', 'data');
        $img = $product->images()->create([
            'url' => '/storage/products/old.webp',
            'local_path' => '/storage/products/old.webp',
            'position' => 0,
            'alt' => 'x',
        ]);

        $this->artisan('images:migrate-to-uploads --dry-run')->assertExitCode(0);

        $img->refresh();
        $this->assertSame('/storage/products/old.webp', $img->local_path);
        Storage::disk('uploads')->assertMissing('products/old.webp');
    }
}
