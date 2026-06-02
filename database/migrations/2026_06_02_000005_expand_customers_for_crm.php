<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Only the name is mandatory now — email is optional for clinic-only
            // clients who never bought online. Native change() works on all drivers.
            $table->string('email')->nullable()->change();

            $table->date('birthdate')->nullable()->after('document');
            $table->string('photo_path')->nullable()->after('birthdate');
            $table->string('instagram')->nullable()->after('photo_path');
            $table->text('address')->nullable()->after('instagram');
            $table->text('notes')->nullable()->after('address');
            // CRM lifecycle helpers (for future messaging / segmentation).
            $table->timestamp('last_visit_at')->nullable()->after('notes');
            $table->boolean('is_active')->default(true)->after('last_visit_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'birthdate', 'photo_path', 'instagram', 'address',
                'notes', 'last_visit_at', 'is_active',
            ]);
            $table->string('email')->nullable(false)->change();
        });
    }
};
