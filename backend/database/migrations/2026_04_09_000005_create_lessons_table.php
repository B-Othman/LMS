<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type')->default('text');
            $table->longText('content_html')->nullable();
            $table->json('content_json')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_previewable')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
