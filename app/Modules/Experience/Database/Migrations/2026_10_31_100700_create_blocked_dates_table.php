<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Days a host is unavailable even though a weekly slot would cover them.
        Schema::create('blocked_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('host_profiles', 'user_id')->cascadeOnDelete();
            $table->date('blocked_on');
            $table->timestamps();

            $table->unique(['host_id', 'blocked_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_dates');
    }
};
