<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('report_type'); // completions, learner_progress, assessments, course_detail
            $table->json('filters')->nullable();
            $table->string('format'); // csv, pdf
            $table->string('file_path')->nullable();
            $table->string('status')->default('processing'); // processing, ready, failed
            $table->text('error_message')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'requested_by', 'created_at']);
            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
