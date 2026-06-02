<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Treatment products are session packages (e.g. "10 sessões de axila").
            $table->boolean('is_treatment')->default(false)->after('is_custom_quote');
            $table->unsignedSmallInteger('sessions_count')->nullable()->after('is_treatment');
            $table->unsignedSmallInteger('session_duration_min')->default(30)->after('sessions_count');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_treatment', 'sessions_count', 'session_duration_min']);
        });
    }
};
