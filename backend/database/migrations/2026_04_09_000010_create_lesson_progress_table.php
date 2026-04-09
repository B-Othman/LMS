<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('not_started');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('progress_percent')->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamps();

            $table->unique(['enrollment_id', 'lesson_id']);
            $table->index(['enrollment_id', 'status']);
            $table->index(['lesson_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progress');
    }
};
