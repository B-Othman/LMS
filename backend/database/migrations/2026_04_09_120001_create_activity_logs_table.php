<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action'); // user.created, course.published, enrollment.created, etc.
            $table->string('entity_type')->nullable(); // user, course, enrollment, certificate, quiz
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('description'); // Human-readable summary
            $table->json('changes')->nullable(); // before/after diff for updates
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — logs are immutable

            $table->index(['tenant_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
