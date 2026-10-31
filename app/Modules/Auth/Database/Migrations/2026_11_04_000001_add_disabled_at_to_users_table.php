<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Set by an admin to lock an account (abuse, fraud). The account and its history stay; it just cannot log in.
            $table->timestamp('disabled_at')->nullable();
            $table->string('disabled_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['disabled_at', 'disabled_reason']);
        });
    }
};
