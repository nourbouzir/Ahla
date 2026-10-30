<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per host; the primary key is the user's id. Tables that belong
        // to a host (experiences, availability, verification requests) point here,
        // so only users who have a host profile can own them.
        Schema::create('host_profiles', function (Blueprint $table) {
            $table->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->text('bio')->nullable();
            $table->string('city', 100)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('photo_path')->nullable();
            // Empty until reviews exist (reviews are a Candidate feature, ADR 0004).
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->timestamps();

            $table->index(['city', 'status']); // search by city among verified hosts
            $table->index('status');           // the admin's list of pending applications
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_profiles');
    }
};
