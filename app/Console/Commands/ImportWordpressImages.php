<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportWordpressImages extends Command
{
    protected $signature = 'images:import-wp
        {--wp-uploads= : Caminho da pasta wp-content/uploads do WordPress (padrão: ../renovasitevelho/...)}
        {--dry-run : Apenas mostra o que seria feito, sem copiar nem alterar}';

    protected $description = 'Traz fisicamente as imagens do WordPress (produtos + blog) para public/uploads/wp e atualiza o banco';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $prefix = $dry ? '[DRY-RUN] ' : '';
        $wpUploads = $this->resolveWpUploads();

        if ($wpUploads) {
            $this->line('Origem WordPress: <info>'.$wpUploads.'</info>');
        } else {
            $this->warn('Pasta wp-content/uploads não encontrada. Só vou apontar o banco para arquivos que JÁ existam em public/uploads/wp/');
            $this->warn('(use --wp-uploads="C:\\caminho\\wp-content\\uploads" se necessário).');
        }
        $this->newLine();

        $stats = (object) ['copied' => 0, 'reused' => 0, 'missing' => 0, 'records' => 0];

        // Garante que o arquivo (caminho relativo do WP) esteja em public/uploads/wp.
        // Retorna a URL pública (/uploads/wp/...) ou null se não achar o arquivo.
        $ensure = function (string $rel) use ($wpUploads, $dry, $stats): ?string {
            $rel = ltrim(rawurldecode($rel), '/');
            $target = 'wp/'.$rel;

            if (Storage::disk('uploads')->exists($target)) {
                $stats->reused++;

                return '/uploads/'.$target;
            }

            if ($wpUploads) {
                $src = $wpUploads.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $rel);
                if (is_file($src)) {
                    if (! $dry) {
                        Storage::disk('uploads')->put($target, file_get_contents($src));
                    }
                    $stats->copied++;

                    return '/uploads/'.$target;
                }
            }

            $stats->missing++;

            return null;
        };

        // ---------- PRODUTOS ----------
        $this->line('Produtos:');
        $productImgs = ProductImage::query()
            ->where('url', 'like', '%/wp-content/uploads/%')
            ->where(function ($q) {
                $q->whereNull('local_path')->orWhere('local_path', 'not like', '/uploads/%');
            })
            ->get();

        foreach ($productImgs as $img) {
            $rel = Str::after($img->url, '/wp-content/uploads/');
            if ($rel === '' || $rel === $img->url) {
                continue;
            }

            $newUrl = $ensure($rel);
            if ($newUrl === null) {
                $this->warn("  ✘ #{$img->id}: não encontrado ({$rel})");

                continue;
            }

            if (! $dry) {
                $img->forceFill(['local_path' => $newUrl])->save();
            }
            $stats->records++;
            $this->line("  ✔ #{$img->id}: {$rel}");
        }

        // ---------- BLOG ----------
        $this->line('Blog:');
        $posts = BlogPost::query()
            ->where(function ($q) {
                $q->where('content_html', 'like', '%/wp-content/uploads/%')
                    ->orWhere('featured_image_url', 'like', '%/wp-content/uploads/%');
            })
            ->get();

        foreach ($posts as $post) {
            $changed = false;

            if ($post->featured_image_url && str_contains($post->featured_image_url, '/wp-content/uploads/')) {
                $newUrl = $ensure(Str::after($post->featured_image_url, '/wp-content/uploads/'));
                if ($newUrl) {
                    if (! $dry) {
                        $post->featured_image_url = $newUrl;
                    }
                    $changed = true;
                }
            }

            $html = (string) $post->content_html;
            // Captura URLs absolutas (https://dominio/wp-content/...) E relativas (/wp-content/...).
            preg_match_all('#(?:https?://[^"\'\s)]*?)?/wp-content/uploads/[^"\'\s)]+\.(?:png|jpe?g|webp|gif)#i', $html, $m);
            foreach (array_unique($m[0] ?? []) as $full) {
                $newUrl = $ensure(Str::after($full, '/wp-content/uploads/'));
                if ($newUrl) {
                    $html = str_replace($full, $newUrl, $html);
                    $changed = true;
                }
            }

            if ($changed) {
                if (! $dry) {
                    $post->content_html = $html;
                    $post->save();
                }
                $stats->records++;
                $this->line("  ✔ post #{$post->id}: {$post->slug}");
            }
        }

        $this->newLine();
        $this->info($prefix."Concluído: {$stats->copied} copiada(s), {$stats->reused} já existiam, {$stats->missing} sem arquivo. Registros atualizados: {$stats->records}.");

        return self::SUCCESS;
    }

    private function resolveWpUploads(): ?string
    {
        if ($opt = $this->option('wp-uploads')) {
            return is_dir($opt) ? rtrim($opt, '/\\') : null;
        }

        $default = base_path('../renovasitevelho/renovalaserdepilacao.com.br/wp-content/uploads');

        return is_dir($default) ? $default : null;
    }
}
