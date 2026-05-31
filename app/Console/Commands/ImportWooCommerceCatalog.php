<?php

namespace App\Console\Commands;

use App\Services\Store\ProductImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ImportWooCommerceCatalog extends Command
{
    protected $signature = 'store:import-woocommerce
        {--connection=woocommerce : Laravel database connection that points to WordPress}
        {--database=renova : WordPress database name when creating the default connection}
        {--prefix=wpk7_ : WordPress table prefix}';

    protected $description = 'Import WooCommerce products, categories, images and Google Merchant metadata.';

    public function handle(ProductImportService $importer): int
    {
        $connection = (string) $this->option('connection');

        if ($connection === 'woocommerce' && ! Config::has('database.connections.woocommerce')) {
            Config::set('database.connections.woocommerce', [
                'driver' => 'mysql',
                'host' => env('WOOCOMMERCE_DB_HOST', env('DB_HOST', '127.0.0.1')),
                'port' => env('WOOCOMMERCE_DB_PORT', env('DB_PORT', '3306')),
                'database' => $this->option('database'),
                'username' => env('WOOCOMMERCE_DB_USERNAME', 'root'),
                'password' => env('WOOCOMMERCE_DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]);
        }

        $result = $importer->import($connection, (string) $this->option('prefix'));

        $this->info("Imported {$result['categories']} categories and {$result['products']} products.");

        return self::SUCCESS;
    }
}
