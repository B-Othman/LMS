<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scorm_runtime_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('launch_session_id')->unique();
            $table->json('cmi_data');                            // stores all cmi.* values
            $table->timestamp('last_updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();

            $table->foreign('launch_session_id')->references('id')->on('package_launch_sessions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scorm_runtime_state');
    }
};
