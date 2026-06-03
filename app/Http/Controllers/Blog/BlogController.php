<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogTerm;
use App\Support\HtmlSanitizer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BlogController extends Controller
{
    public function index(Request $request): Response
    {
        $category = $request->query('categoria')
            ? BlogTerm::query()
                ->where('taxonomy', 'category')
                ->where('slug', $request->query('categoria'))
                ->first()
            : null;

        $posts = BlogPost::query()
            ->published()
            ->with(['terms' => fn ($query) => $query->where('taxonomy', 'category')])
            ->when($category, fn ($query) => $query->whereHas('terms', fn ($termQuery) => $termQuery->whereKey($category->id)))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return Inertia::render('Blog/Index', [
            'posts' => $posts->through(fn (BlogPost $post) => $this->cardPayload($post)),
            'categories' => BlogTerm::query()
                ->where('taxonomy', 'category')
                ->where('post_count', '>', 0)
                ->orderBy('name')
                ->get(['name', 'slug', 'post_count']),
            'activeCategory' => $category ? [
                'name' => $category->name,
                'slug' => $category->slug,
            ] : null,
            'seo' => [
                'title' => 'Blog - Renova Laser Depilação',
                'description' => 'Conteúdos da Renova Laser sobre depilação a laser, cuidados com a pele, tecnologia e resultados.',
                'canonical' => route('blog.index'),
            ],
        ]);
    }

    public function show(BlogPost $blogPost): Response
    {
        abort_unless($blogPost->status === 'publish', 404);

        $blogPost->load('terms');
        $related = BlogPost::query()
            ->published()
            ->whereKeyNot($blogPost->id)
            ->latest('published_at')
            ->limit(3)
            ->get()
            ->map(fn (BlogPost $post) => $this->cardPayload($post));

        return Inertia::render('Blog/Show', [
            'post' => $this->postPayload($blogPost),
            'related' => $related,
            'structuredData' => $this->structuredData($blogPost),
        ]);
    }

    private function cardPayload(BlogPost $post): array
    {
        return [
            'title' => $post->title,
            'slug' => $post->slug,
            'href' => url('/'.$post->slug),
            'excerpt' => $post->excerpt,
            'published_at' => $post->published_at?->toDateString(),
            'image_url' => $post->featured_image_url,
            'image_alt' => $post->featured_image_alt ?: $post->title,
            'categories' => $post->terms
                ->where('taxonomy', 'category')
                ->map(fn (BlogTerm $term) => ['name' => $term->name, 'slug' => $term->slug])
                ->values(),
        ];
    }

    private function postPayload(BlogPost $post): array
    {
        return [
            ...$this->cardPayload($post),
            'content_html' => HtmlSanitizer::clean($post->content_html),
            'modified_at' => $post->modified_at?->toDateString(),
            'tags' => $post->terms
                ->where('taxonomy', 'post_tag')
                ->map(fn (BlogTerm $term) => ['name' => $term->name, 'slug' => $term->slug])
                ->values(),
            'seo' => [
                'title' => $post->seo_title ?: $post->title,
                'description' => $post->seo_description ?: $post->excerpt,
                'focus_keyword' => $post->seo_focus_keyword,
                'canonical' => $post->canonical_url ?: url('/'.$post->slug),
                'robots' => $post->is_indexable ? 'index, follow' : 'noindex, follow',
            ],
        ];
    }

    private function structuredData(BlogPost $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $post->seo_title ?: $post->title,
            'description' => $post->seo_description ?: $post->excerpt,
            'image' => $post->featured_image_url ? [$post->featured_image_url] : [],
            'datePublished' => $post->published_at?->toAtomString(),
            'dateModified' => $post->modified_at?->toAtomString(),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Renova Laser Depilação',
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Renova Laser Depilação',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
                ],
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => url('/'.$post->slug),
            ],
        ];
    }
}
