<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_launch_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_version_id');
            $table->unsignedBigInteger('enrollment_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('launched_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('status')->default('active');  // active|completed|failed|abandoned
            $table->timestamps();

            $table->foreign('package_version_id')->references('id')->on('content_package_versions')->cascadeOnDelete();
            $table->foreign('enrollment_id')->references('id')->on('enrollments')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['user_id', 'enrollment_id']);
            $table->index('package_version_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_launch_sessions');
    }
};
