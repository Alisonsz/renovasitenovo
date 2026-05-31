<?php

namespace Database\Seeders;

use App\Services\Store\ProductImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        Config::set('database.connections.woocommerce', [
            'driver' => 'mysql',
            'host' => env('WOOCOMMERCE_DB_HOST', env('DB_HOST', '127.0.0.1')),
            'port' => env('WOOCOMMERCE_DB_PORT', env('DB_PORT', '3306')),
            'database' => env('WOOCOMMERCE_DB_DATABASE', 'renova'),
            'username' => env('WOOCOMMERCE_DB_USERNAME', 'root'),
            'password' => env('WOOCOMMERCE_DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ]);

        app(ProductImportService::class)->import('woocommerce', env('WOOCOMMERCE_DB_PREFIX', 'wpk7_'));
    }
}
