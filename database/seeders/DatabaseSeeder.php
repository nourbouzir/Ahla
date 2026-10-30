<?php

namespace Database\Seeders;

use App\Modules\Modules;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Each module seeds itself (app/Modules/<Module>/Database/Seeders); this
     * file only runs them in dependency order, so it never needs editing when
     * a module adds seed data.
     */
    public function run(): void
    {
        $this->call(Modules::seeders());
    }
}
