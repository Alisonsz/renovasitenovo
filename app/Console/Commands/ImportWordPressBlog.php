<?php

namespace App\Console\Commands;

use App\Services\Blog\BlogImportService;
use Illuminate\Console\Command;

class ImportWordPressBlog extends Command
{
    protected $signature = 'blog:import-wordpress
        {--connection=wordpress : Database connection containing the WordPress tables}
        {--prefix=wpk7_ : WordPress table prefix}';

    protected $description = 'Import WordPress blog posts, taxonomies, featured images and Yoast SEO metadata.';

    public function handle(BlogImportService $importer): int
    {
        $result = $importer->import(
            (string) $this->option('connection'),
            (string) $this->option('prefix'),
        );

        $this->info("Imported {$result['terms']} blog terms and {$result['posts']} blog posts.");

        return self::SUCCESS;
    }
}
