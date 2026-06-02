<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            // Link back to the order that generated it (idempotency for auto-create).
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');                         // snapshot of the package name
            $table->unsignedSmallInteger('total_sessions')->default(1);
            $table->unsignedSmallInteger('completed_sessions')->default(0);
            $table->unsignedSmallInteger('session_duration_min')->default(30);
            $table->string('status')->default('active');    // active | completed | cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }
};
