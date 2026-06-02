<?php

namespace Database\Seeders;

use App\Services\Blog\BlogImportService;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        if (! $this->canConnectToWordPress()) {
            return;
        }

        app(BlogImportService::class)->import(
            env('WORDPRESS_DB_CONNECTION', 'wordpress'),
            env('WORDPRESS_DB_PREFIX', 'wpk7_'),
        );
    }

    private function canConnectToWordPress(): bool
    {
        try {
            \DB::connection(env('WORDPRESS_DB_CONNECTION', 'wordpress'))->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
