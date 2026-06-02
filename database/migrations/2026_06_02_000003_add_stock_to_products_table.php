<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Numeric stock. Null = unlimited (e.g. services / custom-quote items
            // that should never be blocked by stock control).
            $table->integer('stock_quantity')->nullable()->after('stock_status');
            $table->boolean('manage_stock')->default(false)->after('stock_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'manage_stock']);
        });
    }
};
