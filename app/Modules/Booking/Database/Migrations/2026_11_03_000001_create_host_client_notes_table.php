<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_client_notes', function (Blueprint $table) {
            $table->id();
            // Private to the host: one note per host and client, never shown to the client.
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tourist_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->unique(['host_id', 'tourist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_client_notes');
    }
};
