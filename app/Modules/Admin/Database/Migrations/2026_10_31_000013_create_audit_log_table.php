<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 80);
            $table->unsignedBigInteger('entity_id');
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamp('created_at');
            $table->index(['entity', 'entity_id', 'created_at'], 'audit_log_entity_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_log');
    }
};
