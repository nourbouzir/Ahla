<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('host_profiles', 'user_id')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // 1 to 6 hours and at most 6 people: checked by the application (ADR 0004,
            // decision 13); MySQL and SQLite cannot share one CHECK constraint syntax.
            $table->unsignedTinyInteger('duration_hours');
            $table->decimal('price_per_person', 10, 3); // TND has 3 decimals, never a float
            $table->unsignedTinyInteger('max_group_size');
            $table->string('meeting_point')->nullable();
            $table->decimal('meeting_lat', 10, 7)->nullable();
            $table->decimal('meeting_lng', 10, 7)->nullable();
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->index(['host_id', 'published']);
            $table->index('price_per_person'); // price filter in search (FR-08)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
