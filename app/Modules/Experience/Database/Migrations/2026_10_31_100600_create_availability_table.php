<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Weekly slots (FR-11). Times are local, Africa/Tunis (ADR 0004, decision 11).
        Schema::create('availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('host_profiles', 'user_id')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // ISO-8601: 1 = Monday ... 7 = Sunday
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['host_id', 'weekday']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability');
    }
};
