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
            $table->foreignId('host_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category');
            $table->text('description');
            $table->unsignedTinyInteger('duration_hours');
            $table->decimal('price_per_person', 10, 3);
            $table->unsignedTinyInteger('max_group_size')->default(6);
            $table->string('photo_url');
            $table->boolean('published')->default(false);
            $table->timestamps();

            $table->index(['published', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiences');
    }
};
