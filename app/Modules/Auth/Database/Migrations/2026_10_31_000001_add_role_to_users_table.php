<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('tourist')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // SQLite keeps the index when the column goes, MySQL drops it with the column.
            if (Schema::hasIndex('users', 'users_role_index')) {
                $table->dropIndex(['role']);
            }
            $table->dropColumn('role');
        });
    }
};
