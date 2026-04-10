<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Speed up completion reports
        Schema::table('enrollments', function (Blueprint $table) {
            $table->index(['course_id', 'status', 'completed_at'], 'enrollments_course_status_completed');
            $table->index(['tenant_id', 'status', 'enrolled_at'], 'enrollments_tenant_status_enrolled');
        });

        // Speed up lesson-level heatmap queries
        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->index(['enrollment_id', 'status', 'completed_at'], 'lp_enrollment_status_completed');
        });

        // Speed up assessment reports
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->index(['quiz_id', 'passed', 'submitted_at'], 'qa_quiz_passed_submitted');
        });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex('enrollments_course_status_completed');
            $table->dropIndex('enrollments_tenant_status_enrolled');
        });

        Schema::table('lesson_progress', function (Blueprint $table) {
            $table->dropIndex('lp_enrollment_status_completed');
        });

        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex('qa_quiz_passed_submitted');
        });
    }
};
