<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per application (a rejected host may re-apply, so a host can have several).
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('host_profiles', 'user_id')->cascadeOnDelete();
            // Path on the private disk, never a public URL (FR-05, NFR-12).
            $table->string('id_document_path')->nullable();
            $table->dateTime('video_call_at')->nullable();
            $table->string('video_call_link')->nullable();
            $table->string('decision', 20)->nullable(); // verified or rejected, empty while pending
            $table->text('reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('decided_at')->nullable();
            $table->timestamps();

            $table->index(['host_id', 'decided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
};
