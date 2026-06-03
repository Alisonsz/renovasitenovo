<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ExportData extends Command
{
    protected $signature = 'data:export
        {--groups=catalog,blog : Comma-separated: catalog,blog,sales,clinic,users,all}
        {--truncate : Add TRUNCATE before inserting each table (clean import)}
        {--path= : Output .sql path (default: storage/app/exports/renova-data-<groups>.sql)}';

    protected $description = 'Export business data as a phpMyAdmin-ready .sql file (INSERTs only).';

    /**
     * Table groups in FK-safe insertion order. Parents before children.
     */
    private array $groups = [
        'users' => ['users'],
        'catalog' => [
            'product_categories',
            'products',
            'product_images',
            'product_category_product',
            'coupons',
        ],
        'blog' => [
            'blog_terms',
            'blog_posts',
            'blog_post_blog_term',
            'seo_redirects',
        ],
        'sales' => [
            'customers',
            'carts',
            'cart_items',
            'orders',
            'order_items',
            'payment_transactions',
        ],
        'clinic' => [
            'professionals',
            'treatments',
            'appointments',
        ],
    ];

    public function handle(): int
    {
        $requested = collect(explode(',', (string) $this->option('groups')))
            ->map(fn ($g) => trim($g))
            ->filter()
            ->values();

        if ($requested->contains('all')) {
            $requested = collect(array_keys($this->groups));
        }

        $unknown = $requested->reject(fn ($g) => isset($this->groups[$g]));
        if ($unknown->isNotEmpty()) {
            $this->error('Grupos desconhecidos: '.$unknown->implode(', '));
            $this->line('Disponíveis: '.implode(', ', array_keys($this->groups)).', all');

            return self::FAILURE;
        }

        // Resolve final ordered table list (dedup, keep order).
        $tables = [];
        foreach ($requested as $group) {
            foreach ($this->groups[$group] as $t) {
                if (! in_array($t, $tables, true)) {
                    $tables[] = $t;
                }
            }
        }

        $truncate = (bool) $this->option('truncate');
        $sql = $this->buildSql($tables, $truncate);

        $path = $this->option('path')
            ?: storage_path('app/exports/renova-data-'.$requested->implode('-').'.sql');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $sql);

        $this->newLine();
        $this->info('Exportado: '.$path);
        $this->line('Tamanho: '.number_format(strlen($sql) / 1024, 1).' KB');
        $this->line('Tabelas: '.implode(', ', $tables));
        $this->newLine();
        $this->line('Importe pela aba "Importar" do phpMyAdmin no banco de destino.');

        return self::SUCCESS;
    }

    private function buildSql(array $tables, bool $truncate): string
    {
        $driver = DB::getDriverName();
        $now = now()->toDateTimeString();

        $out = [];
        $out[] = '-- Renova Laser — export de dados';
        $out[] = '-- Gerado em '.$now;
        $out[] = '-- Grupos/tabelas: '.implode(', ', $tables);
        $out[] = '-- Importar via phpMyAdmin > Importar (charset utf8mb4).';
        $out[] = '';
        $out[] = 'SET NAMES utf8mb4;';
        $out[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $out[] = 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
        $out[] = '';

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->warn("Pulando (não existe): {$table}");

                continue;
            }

            $columns = Schema::getColumnListing($table);
            $count = DB::table($table)->count();
            $out[] = '-- ----------------------------------------------------------';
            $out[] = "-- Tabela `{$table}` ({$count} registros)";
            $out[] = '-- ----------------------------------------------------------';

            if ($truncate) {
                // DELETE (not TRUNCATE): TRUNCATE ignores FOREIGN_KEY_CHECKS=0 and
                // fails with #1701 on tables referenced by a foreign key.
                $out[] = "DELETE FROM `{$table}`;";
            }

            if ($count === 0) {
                $out[] = '-- (sem registros)';
                $out[] = '';

                continue;
            }

            $colList = collect($columns)->map(fn ($c) => "`{$c}`")->implode(', ');

            // Stream rows in chunks to keep memory low; batch INSERTs of 100.
            DB::table($table)->orderBy($columns[0])->chunk(100, function ($rows) use (&$out, $table, $columns, $colList) {
                $values = [];
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($columns as $col) {
                        $vals[] = $this->quote($row->{$col} ?? null);
                    }
                    $values[] = '('.implode(', ', $vals).')';
                }
                $out[] = "INSERT INTO `{$table}` ({$colList}) VALUES";
                $out[] = implode(",\n", $values).';';
            });

            $out[] = '';
        }

        $out[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $out[] = '';

        return implode("\n", $out);
    }

    /** Safely quote a scalar value for a MySQL SQL literal. */
    private function quote(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $value = (string) $value;

        // Escape backslashes, single quotes and control chars MySQL cares about.
        $escaped = str_replace(
            ['\\', "'", "\n", "\r", "\x00", "\x1a"],
            ['\\\\', "\\'", '\\n', '\\r', '\\0', '\\Z'],
            $value
        );

        return "'".$escaped."'";
    }
}
