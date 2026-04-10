<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_packages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('course_id');
            $table->string('title');
            $table->string('standard')->default('scorm_12');  // scorm_12 | scorm_2004 | xapi | native
            $table->string('original_filename');
            $table->string('file_path');                       // S3 path to original ZIP
            $table->unsignedBigInteger('file_size_bytes');
            $table->string('status')->default('uploaded');     // uploaded|validating|valid|invalid|published|failed
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users');

            $table->index(['tenant_id', 'course_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_packages');
    }
};
