<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('host_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->string('display_name');
            $table->text('bio');
            $table->string('city')->index();
            $table->json('languages');
            $table->json('interests');
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedSmallInteger('rating_count')->default(0);
            $table->string('avatar_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('host_profiles');
    }
};
