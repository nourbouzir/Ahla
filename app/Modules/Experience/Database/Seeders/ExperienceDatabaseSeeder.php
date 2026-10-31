<?php

namespace App\Modules\Experience\Database\Seeders;

use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Services\HostDirectory;
use Illuminate\Database\Seeder;

class ExperienceDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $experiences = [
            'Amel Ben Youssef' => [
                [
                    'title' => 'The medina, behind the blue doors',
                    'category' => 'Culture & craft',
                    'description' => 'Follow the scent of jasmine through hidden funduqs, artisan workshops and quiet courtyards that most visitors walk right past.',
                    'duration_hours' => 3,
                    'price_per_person' => '65.000',
                    'max_group_size' => 5,
                    'photo_url' => 'https://images.unsplash.com/photo-1531058020387-3be344556be6?auto=format&fit=crop&w=1000&q=85',
                ],
                [
                    'title' => 'A market morning with Amel',
                    'category' => 'Food & market',
                    'description' => 'Shop the stalls I grew up with, learn what is in season, then sit down to make a proper Tunisian brik together.',
                    'duration_hours' => 2,
                    'price_per_person' => '48.000',
                    'max_group_size' => 4,
                    'photo_url' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1000&q=85',
                ],
            ],
            'Youssef Trabelsi' => [
                [
                    'title' => 'Sidi Bou Said, after the crowds',
                    'category' => 'Art & slow living',
                    'description' => 'Take the long way around the village, find the old artists’ corners and watch the Gulf turn gold from my favourite terrace.',
                    'duration_hours' => 3,
                    'price_per_person' => '72.000',
                    'max_group_size' => 6,
                    'photo_url' => 'https://images.unsplash.com/photo-1516483638261-f4dbaf036963?auto=format&fit=crop&w=1000&q=85',
                ],
            ],
            'Ines Gharbi' => [
                [
                    'title' => 'Clay, salt air & a tiny studio',
                    'category' => 'Make something',
                    'description' => 'Spend an unhurried afternoon learning hand-building in my sunlit studio, then walk down to the sea with clay still under our nails.',
                    'duration_hours' => 3,
                    'price_per_person' => '84.000',
                    'max_group_size' => 4,
                    'photo_url' => 'https://images.unsplash.com/photo-1565193298595-6e9a1c4e3a9d?auto=format&fit=crop&w=1000&q=85',
                ],
            ],
            'Mehdi Louati' => [
                [
                    'title' => 'Lunch like a Tunisian family',
                    'category' => 'Food & market',
                    'description' => 'A relaxed walk for ingredients, a few family recipes and a long table. Come hungry; leave with a little more than a full stomach.',
                    'duration_hours' => 4,
                    'price_per_person' => '95.000',
                    'max_group_size' => 6,
                    'photo_url' => 'https://images.unsplash.com/photo-1555939594-58d7cb561ad1?auto=format&fit=crop&w=1000&q=85',
                ],
            ],
        ];

        foreach ($experiences as $displayName => $listings) {
            $host = app(HostDirectory::class)->findByDisplayName($displayName);

            foreach ($listings as $listing) {
                Experience::updateOrCreate(
                    ['host_profile_id' => $host->id, 'title' => $listing['title']],
                    [...$listing, 'interests' => [$listing['category']], 'published' => true],
                );
            }
        }
    }
}
