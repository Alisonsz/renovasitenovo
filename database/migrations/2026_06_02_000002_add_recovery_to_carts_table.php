<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // Email captured at the very start of checkout (email-first flow).
            $table->string('email')->nullable()->after('customer_id');
            $table->string('customer_name')->nullable()->after('email');

            // Lifecycle: active -> abandoned -> recovered -> converted.
            $table->string('status')->default('active')->after('customer_name');

            // Public, non-guessable token for the recovery link (separate from the session uuid).
            $table->string('recovery_token', 64)->nullable()->unique()->after('status');

            // Drives the "inactive for N hours" abandonment trigger and email throttling.
            $table->timestamp('last_activity_at')->nullable()->after('total_cents');
            $table->timestamp('recovery_email_sent_at')->nullable()->after('last_activity_at');
            $table->timestamp('recovered_at')->nullable()->after('recovery_email_sent_at');
            $table->timestamp('converted_at')->nullable()->after('recovered_at');

            // Recovery coupon minted for this specific cart.
            $table->foreignId('recovery_coupon_id')->nullable()->after('converted_at')
                ->constrained('coupons')->nullOnDelete();

            $table->index(['status', 'last_activity_at']);
        });
    }

    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recovery_coupon_id');
            $table->dropIndex(['status', 'last_activity_at']);
            $table->dropColumn([
                'email',
                'customer_name',
                'status',
                'recovery_token',
                'last_activity_at',
                'recovery_email_sent_at',
                'recovered_at',
                'converted_at',
            ]);
        });
    }
};
