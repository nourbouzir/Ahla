<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
            $table->index(['host_profile_id', 'day_of_week']);
        });

        Schema::create('blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_profile_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('reason', 160)->nullable();
            $table->timestamps();
            $table->unique(['host_profile_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_dates');
        Schema::dropIfExists('availabilities');
    }
};
