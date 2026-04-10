<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('channel'); // email, in_app
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text')->nullable();
            $table->json('data')->nullable();
            $table->string('status')->default('pending'); // pending, sent, failed, read
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('failed_reason')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['tenant_id', 'user_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
