<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'wp_product_id',
        'primary_category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'sku',
        'regular_price_cents',
        'sale_price_cents',
        'price_cents',
        'currency',
        'stock_status',
        'is_active',
        'is_custom_quote',
        'merchant_visibility',
        'merchant_status',
        'merchant_google_id',
        'merchant_brand',
        'merchant_condition',
        'merchant_age_group',
        'merchant_gender',
        'merchant_color',
        'merchant_size',
        'merchant_is_bundle',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'regular_price_cents' => 'integer',
            'sale_price_cents' => 'integer',
            'price_cents' => 'integer',
            'is_active' => 'boolean',
            'is_custom_quote' => 'boolean',
            'merchant_is_bundle' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'primary_category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'product_category_product');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
