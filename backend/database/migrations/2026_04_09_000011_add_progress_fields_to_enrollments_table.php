<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unsignedInteger('progress_percent')->default(0)->after('status');
            $table->unsignedInteger('completed_lessons_count')->default(0)->after('progress_percent');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['progress_percent', 'completed_lessons_count']);
        });
    }
};
