<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('submitted')->index();
            $table->string('evidence_path');
            $table->timestamp('video_call_at')->nullable();
            $table->string('video_call_link')->nullable();
            $table->string('decision', 20)->nullable();
            $table->text('decision_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->index(['host_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
