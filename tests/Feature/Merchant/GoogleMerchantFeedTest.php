<?php

namespace Tests\Feature\Merchant;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleMerchantFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_merchant_feed_renders_active_syncable_products(): void
    {
        $product = Product::factory()->create([
            'name' => 'Combo Virilha',
            'slug' => 'combo-virilha',
            'short_description' => 'Combo de depilacao a laser.',
            'price_cents' => 90000,
            'regular_price_cents' => 112500,
            'sale_price_cents' => 90000,
            'merchant_google_id' => 'gla_123',
            'merchant_brand' => 'Renova Laser Depilacao',
            'merchant_condition' => 'new',
            'merchant_age_group' => 'adult',
            'merchant_gender' => 'female',
            'merchant_color' => 'Padrao',
            'merchant_size' => 'Padrao',
            'merchant_is_bundle' => true,
            'merchant_visibility' => 'sync-and-show',
            'is_active' => true,
        ]);
        ProductImage::query()->create([
            'product_id' => $product->id,
            'url' => 'https://example.com/combo.jpg',
            'position' => 0,
        ]);
        Product::factory()->create([
            'merchant_visibility' => 'dont-sync-and-show',
            'is_active' => true,
        ]);

        $response = $this->get('/merchant/google.xml');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/xml');
        $response->assertSee('<g:id>gla_123</g:id>', false);
        $response->assertSee('<g:title>Combo Virilha</g:title>', false);
        $response->assertSee('<g:price>1125.00 BRL</g:price>', false);
        $response->assertSee('<g:sale_price>900.00 BRL</g:sale_price>', false);
        $response->assertDontSee('dont-sync-and-show');
    }
}
