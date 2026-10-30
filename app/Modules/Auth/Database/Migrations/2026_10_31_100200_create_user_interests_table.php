<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One table for both roles: a host's interests and a tourist's
        // preferences are the same kind of data, and search (FR-08) and
        // recommendations (FR-09) match one against the other.
        Schema::create('user_interests', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('interest', 50);

            $table->primary(['user_id', 'interest']);
            $table->index('interest');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_interests');
    }
};
