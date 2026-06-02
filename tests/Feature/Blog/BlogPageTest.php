<?php

namespace Tests\Feature\Blog;

use App\Models\BlogPost;
use App\Models\BlogTerm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_lists_published_posts(): void
    {
        $post = BlogPost::query()->create([
            'wp_post_id' => 10,
            'title' => 'Depilação a laser no verão',
            'slug' => 'depilacao-a-laser-no-verao',
            'excerpt' => 'Cuidados antes e depois da sessão.',
            'content_html' => '<p>Conteúdo</p>',
            'status' => 'publish',
            'published_at' => now(),
            'seo_title' => 'SEO title',
            'seo_description' => 'SEO description',
        ]);

        $category = BlogTerm::query()->create([
            'wp_term_id' => 1,
            'taxonomy' => 'category',
            'name' => 'Sem categoria',
            'slug' => 'sem-categoria',
            'post_count' => 1,
        ]);

        $post->terms()->attach($category);

        $response = $this->get('/blog');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Blog/Index')
                ->where('posts.data.0.slug', 'depilacao-a-laser-no-verao')
                ->where('categories.0.slug', 'sem-categoria'));
    }

    public function test_blog_post_keeps_root_slug_and_yoast_metadata(): void
    {
        BlogPost::query()->create([
            'wp_post_id' => 11,
            'title' => 'Depilação a laser dói?',
            'slug' => 'depilacao-a-laser-doi',
            'excerpt' => 'Entenda a sensação do procedimento.',
            'content_html' => '<p>Artigo importado.</p>',
            'status' => 'publish',
            'published_at' => now(),
            'modified_at' => now(),
            'seo_title' => 'Depilação a laser dói? Entenda',
            'seo_description' => 'Aprenda a lidar com a dor da depilação a laser.',
            'seo_focus_keyword' => 'depilação a laser dói',
            'canonical_url' => 'https://example.test/depilacao-a-laser-doi',
        ]);

        $response = $this->get('/depilacao-a-laser-doi');

        $response
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Blog/Show')
                ->where('post.slug', 'depilacao-a-laser-doi')
                ->where('post.seo.title', 'Depilação a laser dói? Entenda')
                ->where('post.seo.description', 'Aprenda a lidar com a dor da depilação a laser.')
                ->where('post.seo.focus_keyword', 'depilação a laser dói')
                ->where('post.seo.canonical', 'https://example.test/depilacao-a-laser-doi'));
    }
}
