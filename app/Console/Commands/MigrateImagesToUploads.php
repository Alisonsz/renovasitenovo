<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateImagesToUploads extends Command
{
    protected $signature = 'images:migrate-to-uploads
        {--dry-run : Apenas mostra o que seria feito, sem alterar nada}
        {--delete-source : Apaga o arquivo antigo em storage/app/public após copiar}';

    protected $description = 'Migra imagens de produto de /storage (symlink) para /uploads (servido direto), copiando os arquivos e atualizando o banco';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $deleteSource = (bool) $this->option('delete-source');
        $prefix = $dry ? '[DRY-RUN] ' : '';

        $images = ProductImage::query()
            ->where(function ($q) {
                $q->where('local_path', 'like', '/storage/%')
                    ->orWhere('url', 'like', '/storage/%');
            })
            ->get();

        if ($images->isEmpty()) {
            $this->info('Nada a migrar: nenhuma imagem aponta para /storage/.');

            return self::SUCCESS;
        }

        $this->line($prefix."Encontradas {$images->count()} imagem(ns) em /storage/.");
        $this->newLine();

        $migrated = 0;
        $missing = 0;

        foreach ($images as $image) {
            $ref = (string) ($image->local_path ?: $image->url);
            $relative = Str::after($ref, '/storage/');

            if ($relative === '' || $relative === $ref) {
                continue;
            }

            $inUploads = Storage::disk('uploads')->exists($relative);
            $inPublic = Storage::disk('public')->exists($relative);

            if (! $inUploads && ! $inPublic) {
                $this->warn("  ✘ #{$image->id}: arquivo não encontrado ({$relative}) — pulando.");
                $missing++;

                continue;
            }

            $newPath = '/uploads/'.$relative;

            if ($dry) {
                $this->line("  → #{$image->id}: {$ref}  ->  {$newPath}".($inUploads ? '  (já existe em uploads)' : ''));
                $migrated++;

                continue;
            }

            // Copia para public/uploads, se ainda não estiver lá.
            if (! $inUploads && $inPublic) {
                Storage::disk('uploads')->put($relative, Storage::disk('public')->get($relative));
            }

            // Atualiza o banco (/storage/ -> /uploads/ nos dois campos).
            $image->forceFill([
                'local_path' => $image->local_path ? str_replace('/storage/', '/uploads/', $image->local_path) : null,
                'url' => str_replace('/storage/', '/uploads/', (string) $image->url),
            ])->save();

            if ($deleteSource && $inPublic) {
                Storage::disk('public')->delete($relative);
            }

            $this->line("  ✔ #{$image->id}: {$ref}  ->  {$newPath}");
            $migrated++;
        }

        $this->newLine();
        $this->info($prefix."Concluído: {$migrated} migrada(s)".($missing ? ", {$missing} sem arquivo" : '').'.');

        if (! $dry && ! $deleteSource && $migrated > 0) {
            $this->line('Os arquivos antigos em storage/app/public/ foram mantidos (rode com --delete-source para apagá-los).');
        }

        return self::SUCCESS;
    }
}
