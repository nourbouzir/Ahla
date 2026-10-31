<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->json('interests')->nullable();
            $table->index('price_per_person');
        });

        DB::table('experiences')->orderBy('id')->get()->each(function (object $experience): void {
            DB::table('experiences')->where('id', $experience->id)->update([
                'interests' => json_encode([$experience->category], JSON_THROW_ON_ERROR),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropIndex(['price_per_person']);
            $table->dropColumn('interests');
        });
    }
};
