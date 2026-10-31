<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tourist_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('host_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('experience_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('group_size');
            $table->string('location')->nullable();
            $table->decimal('displayed_price', 10, 3);
            $table->string('status', 20)->default('requested')->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->index(['host_id', 'date', 'status']);
            $table->index(['tourist_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
