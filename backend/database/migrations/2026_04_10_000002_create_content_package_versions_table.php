<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_package_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id');
            $table->unsignedSmallInteger('version_number')->default(1);
            $table->string('extracted_path');                    // S3 prefix where assets live
            $table->json('manifest_data');                       // parsed imsmanifest.xml
            $table->string('launch_path');                       // relative path to SCO entry point
            $table->unsignedSmallInteger('sco_count')->default(1);
            $table->json('metadata')->nullable();                // title, description, etc. from manifest
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('package_id')->references('id')->on('content_packages')->cascadeOnDelete();
            $table->index('package_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_package_versions');
    }
};
