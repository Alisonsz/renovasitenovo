<?php

namespace App\Services\Blog;

use App\Models\BlogPost;
use App\Models\BlogTerm;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BlogImportService
{
    /** @return array{terms:int, posts:int} */
    public function import(string $connection = 'wordpress', string $prefix = 'wpk7_'): array
    {
        return [
            'terms' => $this->importTerms($connection, $prefix),
            'posts' => $this->importPosts($connection, $prefix),
        ];
    }

    private function importTerms(string $connection, string $prefix): int
    {
        $rows = DB::connection($connection)
            ->table("{$prefix}terms as t")
            ->join("{$prefix}term_taxonomy as tt", 'tt.term_id', '=', 't.term_id')
            ->whereIn('tt.taxonomy', ['category', 'post_tag'])
            ->orderBy('tt.taxonomy')
            ->orderBy('t.name')
            ->get([
                't.term_id',
                't.name',
                't.slug',
                'tt.taxonomy',
                'tt.count',
                'tt.description',
            ]);

        foreach ($rows as $row) {
            BlogTerm::query()->updateOrCreate(
                ['wp_term_id' => $row->term_id],
                [
                    'taxonomy' => $row->taxonomy,
                    'name' => $this->normalizeText($row->name),
                    'slug' => $row->slug,
                    'post_count' => (int) $row->count,
                    'metadata' => [
                        'description' => $this->normalizeText($row->description),
                    ],
                ],
            );
        }

        return $rows->count();
    }

    private function importPosts(string $connection, string $prefix): int
    {
        $rows = DB::connection($connection)
            ->table("{$prefix}posts")
            ->where('post_type', 'post')
            ->where('post_status', 'publish')
            ->orderBy('post_date')
            ->get();

        foreach ($rows as $row) {
            $meta = $this->postMeta($connection, $prefix, $row->ID);
            $image = $this->featuredImage($connection, $prefix, (int) ($meta['_thumbnail_id'] ?? 0));
            $content = $this->normalizePostHtml((string) $row->post_content);
            $excerpt = $row->post_excerpt ?: Str::limit(strip_tags($content), 180, '');

            $post = BlogPost::query()->updateOrCreate(
                ['wp_post_id' => $row->ID],
                [
                    'title' => $this->normalizeText($row->post_title),
                    'slug' => $row->post_name ?: Str::slug($row->post_title),
                    'excerpt' => $this->normalizeText($excerpt),
                    'content_html' => $content,
                    'status' => $row->post_status,
                    'published_at' => $row->post_date,
                    'modified_at' => $row->post_modified,
                    'featured_image_url' => $image['url'] ?? null,
                    'featured_image_alt' => $image['alt'] ?? null,
                    'seo_title' => $this->normalizeText($meta['_yoast_wpseo_title'] ?? $row->post_title),
                    'seo_description' => $this->normalizeText($meta['_yoast_wpseo_metadesc'] ?? $excerpt),
                    'seo_focus_keyword' => $this->normalizeText($meta['_yoast_wpseo_focuskw'] ?? null),
                    'canonical_url' => $this->canonicalUrl($meta, $row->post_name),
                    'is_indexable' => ($meta['_yoast_wpseo_meta-robots-noindex'] ?? '') !== '1',
                    'metadata' => [
                        'wordpress_meta' => $meta,
                        'wordpress_guid' => $row->guid ?? null,
                        'yoast_primary_category' => $meta['_yoast_wpseo_primary_category'] ?? null,
                    ],
                ],
            );

            $post->terms()->sync($this->termsForPost($connection, $prefix, $row->ID)->pluck('id')->all());
        }

        return $rows->count();
    }

    private function postMeta(string $connection, string $prefix, int $postId): array
    {
        return DB::connection($connection)
            ->table("{$prefix}postmeta")
            ->where('post_id', $postId)
            ->get(['meta_key', 'meta_value'])
            ->filter(fn ($row) => $row->meta_key !== null)
            ->mapWithKeys(fn ($row) => [$row->meta_key => $row->meta_value])
            ->all();
    }

    /** @return Collection<int, BlogTerm> */
    private function termsForPost(string $connection, string $prefix, int $postId): Collection
    {
        $termIds = DB::connection($connection)
            ->table("{$prefix}term_relationships as tr")
            ->join("{$prefix}term_taxonomy as tt", 'tt.term_taxonomy_id', '=', 'tr.term_taxonomy_id')
            ->where('tr.object_id', $postId)
            ->whereIn('tt.taxonomy', ['category', 'post_tag'])
            ->pluck('tt.term_id');

        return BlogTerm::query()->whereIn('wp_term_id', $termIds->all())->get();
    }

    private function featuredImage(string $connection, string $prefix, int $attachmentId): ?array
    {
        if ($attachmentId <= 0) {
            return null;
        }

        $attachment = DB::connection($connection)
            ->table("{$prefix}posts")
            ->where('ID', $attachmentId)
            ->first(['ID', 'post_title', 'guid']);

        if (! $attachment || ! $attachment->guid) {
            return null;
        }

        $meta = $this->postMeta($connection, $prefix, $attachmentId);

        return [
            'url' => $this->normalizeUrl((string) $attachment->guid),
            'alt' => $this->normalizeText($meta['_wp_attachment_image_alt'] ?? $attachment->post_title),
        ];
    }

    private function canonicalUrl(array $meta, ?string $slug): ?string
    {
        $canonical = $meta['_yoast_wpseo_canonical'] ?? null;

        if ($canonical) {
            return $this->normalizeUrl($canonical);
        }

        return null;
    }

    private function normalizePostHtml(string $html): string
    {
        $html = $this->normalizeText($html) ?? '';
        $html = preg_replace('/<!--\\s*\\/?wp:[^>]*-->/', '', $html) ?? $html;

        if (! str_contains($html, '<p') && ! str_contains($html, '<h2') && str_contains($html, "\n")) {
            $html = collect(preg_split('/\R{2,}/', trim($html)) ?: [])
                ->map(fn ($paragraph) => trim($paragraph))
                ->filter()
                ->map(fn ($paragraph) => '<p>'.nl2br($paragraph, false).'</p>')
                ->implode("\n");
        }

        return $this->normalizeUrl($html);
    }

    private function normalizeUrl(string $value): string
    {
        $base = rtrim((string) config('app.url'), '/');

        $value = str_replace([
            'https://renovalaserdepilacao.com.br/wp-content/uploads',
            'http://renovalaserdepilacao.com.br/wp-content/uploads',
            'https://www.renovalaserdepilacao.com.br/wp-content/uploads',
            'http://www.renovalaserdepilacao.com.br/wp-content/uploads',
        ], '/wp-content/uploads', $value);

        return str_replace([
            'https://renovalaserdepilacao.com.br',
            'http://renovalaserdepilacao.com.br',
            'https://www.renovalaserdepilacao.com.br',
            'http://www.renovalaserdepilacao.com.br',
        ], $base, $value);
    }

    private function normalizeText(?string $value): ?string
    {
        return $value === null ? null : html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
