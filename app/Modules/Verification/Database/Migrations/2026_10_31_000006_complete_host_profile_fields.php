<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('host_profiles', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('photo_url')->nullable();
            $table->decimal('rating_avg', 3, 2)->nullable()->default(null)->change();
            $table->index(['city', 'status'], 'host_profiles_city_status_index');
        });

        DB::table('host_profiles')
            ->whereNotNull('avatar_url')
            ->update(['photo_url' => DB::raw('avatar_url')]);

        Schema::table('host_profiles', function (Blueprint $table) {
            $table->dropColumn(['languages', 'interests', 'avatar_url', 'rating_count']);
        });
    }

    public function down(): void
    {
        DB::table('host_profiles')
            ->whereNull('rating_avg')
            ->update(['rating_avg' => 0]);

        Schema::table('host_profiles', function (Blueprint $table) {
            $table->json('languages')->nullable();
            $table->json('interests')->nullable();
            $table->string('avatar_url')->nullable();
            $table->unsignedSmallInteger('rating_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0)->change();
        });

        DB::table('host_profiles')
            ->whereNotNull('photo_url')
            ->update(['avatar_url' => DB::raw('photo_url')]);

        Schema::table('host_profiles', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'photo_url']);
            $table->dropIndex('host_profiles_city_status_index');
        });
    }
};
