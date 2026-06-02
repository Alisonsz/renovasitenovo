<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BlogPostController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/BlogPosts/Index', [
            'posts' => BlogPost::query()
                ->latest('published_at')
                ->paginate(15)
                ->through(fn (BlogPost $post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'status' => $post->status,
                    'published_at' => $post->published_at?->toDateString(),
                    'seo_title' => $post->seo_title,
                ]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/BlogPosts/Form', [
            'post' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BlogPost::query()->create($this->validatedData($request));

        return redirect()->route('admin.blog-posts.index');
    }

    public function edit(BlogPost $blogPost): Response
    {
        return Inertia::render('Admin/BlogPosts/Form', [
            'post' => [
                'id' => $blogPost->id,
                'title' => $blogPost->title,
                'slug' => $blogPost->slug,
                'excerpt' => $blogPost->excerpt,
                'content_html' => $blogPost->content_html,
                'status' => $blogPost->status,
                'published_at' => $blogPost->published_at?->format('Y-m-d\TH:i'),
                'featured_image_url' => $blogPost->featured_image_url,
                'featured_image_alt' => $blogPost->featured_image_alt,
                'seo_title' => $blogPost->seo_title,
                'seo_description' => $blogPost->seo_description,
                'seo_focus_keyword' => $blogPost->seo_focus_keyword,
                'canonical_url' => $blogPost->canonical_url,
                'is_indexable' => $blogPost->is_indexable,
            ],
        ]);
    }

    public function update(Request $request, BlogPost $blogPost): RedirectResponse
    {
        $blogPost->update($this->validatedData($request, $blogPost));

        return redirect()->route('admin.blog-posts.index');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $blogPost->terms()->detach();
        $blogPost->delete();

        return redirect()->route('admin.blog-posts.index');
    }

    private function validatedData(Request $request, ?BlogPost $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:blog_posts,slug,'.($post?->id ?: 'NULL')],
            'excerpt' => ['nullable', 'string'],
            'content_html' => ['nullable', 'string'],
            'status' => ['required', 'in:publish,draft'],
            'published_at' => ['nullable', 'date'],
            'featured_image_url' => ['nullable', 'string', 'max:500'],
            'featured_image_alt' => ['nullable', 'string', 'max:255'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'seo_focus_keyword' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'string', 'max:500'],
            'is_indexable' => ['boolean'],
        ]);

        return [
            'wp_post_id' => $post?->wp_post_id ?: (int) now()->format('YmdHis'),
            'title' => $data['title'],
            'slug' => $data['slug'] ?: Str::slug($data['title']),
            'excerpt' => $data['excerpt'] ?? null,
            'content_html' => $data['content_html'] ?? null,
            'status' => $data['status'],
            'published_at' => $data['published_at'] ?? now(),
            'modified_at' => now(),
            'featured_image_url' => $data['featured_image_url'] ?? null,
            'featured_image_alt' => $data['featured_image_alt'] ?? null,
            'seo_title' => $data['seo_title'] ?? $data['title'],
            'seo_description' => $data['seo_description'] ?? $data['excerpt'] ?? null,
            'seo_focus_keyword' => $data['seo_focus_keyword'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'is_indexable' => (bool) ($data['is_indexable'] ?? true),
        ];
    }
}
