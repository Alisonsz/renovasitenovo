<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_terms', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_term_id')->unique();
            $table->string('taxonomy', 40)->index();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('post_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['taxonomy', 'slug']);
        });

        Schema::create('blog_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('wp_post_id')->unique();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('status')->default('publish')->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('modified_at')->nullable();
            $table->string('featured_image_url')->nullable();
            $table->string('featured_image_alt')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('seo_focus_keyword')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('is_indexable')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('blog_post_blog_term', function (Blueprint $table) {
            $table->foreignId('blog_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_term_id')->constrained()->cascadeOnDelete();
            $table->primary(['blog_post_id', 'blog_term_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_blog_term');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_terms');
    }
};
