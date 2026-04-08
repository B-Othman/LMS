<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('password');
            $table->string('avatar_path')->nullable()->after('status');
            $table->timestamp('last_login_at')->nullable()->after('avatar_path');
            $table->softDeletes();
        });

        // Migrate is_active boolean to status enum
        DB::table('users')->where('is_active', false)->update(['status' => 'inactive']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('password');
        });

        DB::table('users')->where('status', '!=', 'active')->update(['is_active' => false]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['status', 'avatar_path', 'last_login_at']);
        });
    }
};
