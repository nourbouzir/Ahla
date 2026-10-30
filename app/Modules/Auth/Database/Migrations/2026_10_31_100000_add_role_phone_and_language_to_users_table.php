<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Extends Laravel's own users table instead of editing its migration.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // A string, not a database enum: the allowed values live in the Role enum.
            $table->string('role', 20)->default('tourist')->index()->after('password');
            $table->string('phone', 30)->nullable()->after('role');
            // Interface language: en, fr or ar (FV NFR-18).
            $table->string('preferred_language', 5)->default('en')->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone', 'preferred_language']);
        });
    }
};
