<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk', 64);
            $table->string('path', 1024);
            $table->string('original_filename');
            $table->string('mime_type', 191);
            $table->unsignedBigInteger('size_bytes');
            $table->string('visibility', 16);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['disk', 'path']);
            $table->index(['tenant_id', 'visibility']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_files');
    }
};
