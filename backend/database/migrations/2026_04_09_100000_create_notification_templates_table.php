<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // enrollment_created, course_completed, etc.
            $table->string('subject_template');
            $table->text('body_html_template');
            $table->text('body_text_template');
            $table->string('channel')->default('both'); // email, in_app, both
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // System defaults have null tenant_id; tenant overrides have tenant_id set
            $table->unique(['tenant_id', 'type']);
            $table->index(['tenant_id', 'type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
