<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_product_id')->nullable()->unique();
            $table->foreignId('primary_category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->string('sku')->nullable();
            $table->unsignedInteger('regular_price_cents')->default(0);
            $table->unsignedInteger('sale_price_cents')->nullable();
            $table->unsignedInteger('price_cents')->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->string('stock_status')->default('instock');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_custom_quote')->default(false);
            $table->string('merchant_visibility')->default('sync-and-show');
            $table->string('merchant_status')->nullable();
            $table->string('merchant_google_id')->nullable();
            $table->string('merchant_brand')->nullable();
            $table->string('merchant_condition')->nullable();
            $table->string('merchant_age_group')->nullable();
            $table->string('merchant_gender')->nullable();
            $table->string('merchant_color')->nullable();
            $table->string('merchant_size')->nullable();
            $table->boolean('merchant_is_bundle')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('product_category_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_category_id')->constrained()->cascadeOnDelete();
            $table->unique(['product_id', 'product_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_category_product');
        Schema::dropIfExists('products');
    }
};
