<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('language', 10);
            $table->timestamps();
            $table->unique(['user_id', 'language']);
            $table->index('language');
        });

        Schema::create('user_interests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('interest', 80);
            $table->timestamps();
            $table->unique(['user_id', 'interest']);
        });

        DB::table('host_profiles')->orderBy('id')->get()->each(function (object $profile): void {
            foreach (json_decode((string) $profile->languages, true) ?: [] as $language) {
                DB::table('user_languages')->insertOrIgnore([
                    'user_id' => $profile->user_id,
                    'language' => $language,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach (json_decode((string) $profile->interests, true) ?: [] as $interest) {
                DB::table('user_interests')->insertOrIgnore([
                    'user_id' => $profile->user_id,
                    'interest' => $interest,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('host_profiles')->orderBy('id')->get()->each(function (object $profile): void {
            $languages = DB::table('user_languages')->where('user_id', $profile->user_id)->pluck('language')->all();
            $interests = DB::table('user_interests')->where('user_id', $profile->user_id)->pluck('interest')->all();

            DB::table('host_profiles')->where('id', $profile->id)->update([
                'languages' => json_encode($languages, JSON_THROW_ON_ERROR),
                'interests' => json_encode($interests, JSON_THROW_ON_ERROR),
            ]);
        });

        Schema::dropIfExists('user_interests');
        Schema::dropIfExists('user_languages');
    }
};
