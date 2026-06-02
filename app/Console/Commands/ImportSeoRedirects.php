<?php

namespace App\Console\Commands;

use App\Services\Seo\RedirectImportService;
use Illuminate\Console\Command;

class ImportSeoRedirects extends Command
{
    protected $signature = 'seo:import-redirects
        {--connection=wordpress : Database connection containing the WordPress options table}
        {--prefix=wpk7_ : WordPress table prefix}';

    protected $description = 'Import Yoast Premium plain redirects from WordPress.';

    public function handle(RedirectImportService $importer): int
    {
        $count = $importer->import(
            (string) $this->option('connection'),
            (string) $this->option('prefix'),
        );

        $this->info("Imported {$count} SEO redirects.");

        return self::SUCCESS;
    }
}
