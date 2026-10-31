<?php

namespace App\Modules\Verification\Database\Seeders;

use App\Modules\Auth\Services\DemoHostAccountService;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Database\Seeder;

class VerificationDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $hosts = [
            [
                'name' => 'Amel Ben Youssef',
                'email' => 'amel@ahla.test',
                'city' => 'Tunis',
                'latitude' => 36.8065,
                'longitude' => 10.1815,
                'bio' => 'A medina-born storyteller who knows which doors open onto the best courtyards, workshops and cups of mint tea.',
                'languages' => ['en', 'fr', 'ar'],
                'interests' => ['history', 'craft', 'food'],
                'rating_avg' => null,
                'photo_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=160&q=85',
            ],
            [
                'name' => 'Youssef Trabelsi',
                'email' => 'youssef@ahla.test',
                'city' => 'Sidi Bou Said',
                'latitude' => 36.8704,
                'longitude' => 10.3470,
                'bio' => 'Painter, coffee drinker and lifelong local. I love the village most when the day-trippers have gone home.',
                'languages' => ['en', 'fr', 'ar', 'it'],
                'interests' => ['art', 'photography', 'history'],
                'rating_avg' => null,
                'photo_url' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=160&q=85',
            ],
            [
                'name' => 'Ines Gharbi',
                'email' => 'ines@ahla.test',
                'city' => 'La Marsa',
                'latitude' => 36.8782,
                'longitude' => 10.3247,
                'bio' => 'Ceramic artist and amateur botanist. I make a little room in my studio for anyone curious about clay and the coast.',
                'languages' => ['en', 'fr', 'ar'],
                'interests' => ['craft', 'nature', 'art'],
                'rating_avg' => null,
                'photo_url' => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&w=160&q=85',
            ],
            [
                'name' => 'Mehdi Louati',
                'email' => 'mehdi@ahla.test',
                'city' => 'Tunis',
                'latitude' => 36.8065,
                'longitude' => 10.1815,
                'bio' => 'History teacher by day, passionate cook by night. The best stories in Tunis are usually told over lunch.',
                'languages' => ['fr', 'ar', 'it'],
                'interests' => ['food', 'history'],
                'rating_avg' => null,
                'photo_url' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=160&q=85',
            ],
        ];

        foreach ($hosts as $hostData) {
            $profileData = collect($hostData)->except(['name', 'email', 'languages', 'interests'])->all();
            $user = app(DemoHostAccountService::class)->ensure($hostData['name'], $hostData['email']);
            $user->languages()->delete();
            $user->languages()->createMany(
                collect($hostData['languages'])->map(fn (string $language) => ['language' => $language])->all(),
            );
            $user->interests()->delete();
            $user->interests()->createMany(
                collect($hostData['interests'])->map(fn (string $interest) => ['interest' => $interest])->all(),
            );

            $profile = HostProfile::firstOrNew(['user_id' => $user->id]);
            $profile->forceFill([
                ...$profileData,
                'display_name' => $hostData['name'],
                'status' => 'verified',
            ])->save();
        }
    }
}
