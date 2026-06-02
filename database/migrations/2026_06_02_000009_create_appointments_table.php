<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('treatment_id')->nullable()->constrained()->nullOnDelete();

            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('session_number')->nullable(); // 3rd of 10, etc.
            $table->string('status')->default('scheduled'); // scheduled|confirmed|completed|no_show|cancelled
            $table->text('notes')->nullable();

            // Future messaging hooks (reminders, no-show charge, etc.).
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['starts_at', 'status']);
            $table->index(['professional_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
