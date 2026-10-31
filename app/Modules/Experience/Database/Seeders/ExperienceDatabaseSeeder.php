<?php

namespace App\Modules\Experience\Database\Seeders;

use App\Modules\Experience\Models\Experience;
use App\Modules\Verification\Models\HostProfile;
use Illuminate\Database\Seeder;

/** Demo experiences and weekly hours for the demo hosts (not seeded in production). */
class ExperienceDatabaseSeeder extends Seeder
{
    /** host => [[title, category, description, hours, price TND per person, max group, meeting point, interests]] */
    private const EXPERIENCES = [
        'Amel Ben Youssef' => [
            ['The medina, behind the blue doors', 'Culture and craft', 'Follow the scent of jasmine through hidden funduqs, artisan workshops and quiet courtyards most visitors walk right past.', 3, '65.000', 5, 'Bab Bhar gate, Tunis', ['history', 'craft']],
            ['A market morning with Amel', 'Food and market', 'Shop the stalls I grew up with, then make a proper Tunisian brik together.', 2, '48.000', 4, 'Central Market entrance', ['food']],
        ],
        'Youssef Trabelsi' => [
            ['Sidi Bou Said, after the crowds', 'Art and slow living', 'The long way around the village, old artists’ corners and the Gulf turning gold from my favourite terrace.', 3, '72.000', 6, 'Sidi Bou Said TGM station', ['art', 'photography']],
        ],
        'Ines Gharbi' => [
            ['Clay, salt air and a tiny studio', 'Make something', 'An unhurried afternoon learning hand-building in my studio, then a walk to the sea.', 3, '84.000', 4, 'La Marsa corniche', ['craft', 'art']],
        ],
        'Mehdi Louati' => [
            ['Lunch like a Tunisian family', 'Food and market', 'A relaxed walk for ingredients, family recipes and a long table. Come hungry.', 4, '95.000', 6, 'Place de la Victoire', ['food', 'history']],
        ],
        'Sana Mejri' => [
            ['Sousse old town after dark', 'History', 'The ribat, the medina walls and the best sweets in town once the day cools down.', 2, '40.000', 6, 'Sousse medina main gate', ['history', 'food']],
        ],
        'Karim Haddad' => [
            ['Golden hour on the Cap Bon coast', 'Photography', 'Quiet coves and the best light of the day. Bring any camera, a phone is fine.', 3, '58.000', 4, 'Hammamet medina', ['photography', 'nature']],
        ],
        'Rania Zouari' => [
            ['Weaving morning in Kairouan', 'Craft', 'Sit at the loom with a third-generation weaver and take home a small piece.', 3, '55.000', 4, 'Great Mosque square', ['craft', 'history']],
        ],
        'Walid Chebbi' => [
            ['Fresh from the Sfax port', 'Food and market', 'The fish market at dawn and a seafood lunch cooked together.', 3, '70.000', 5, 'Sfax fish market', ['food']],
        ],
        'Leila Ben Salah' => [
            ['Djerba lanes and pottery', 'Craft', 'Whitewashed lanes, an island market and a pottery session in my courtyard.', 4, '78.000', 5, 'Houmt Souk clock tower', ['craft', 'art']],
            ['Flamingos and salt flats', 'Nature', 'A calm walk to the lagoon at the quietest hour of the day.', 2, '35.000', 6, 'Djerba lagoon car park', ['nature']],
        ],
        'Hichem Dridi' => [
            ['Sunrise between the palm groves', 'Nature', 'Up before the heat: palms, old brickwork and mint tea in the oasis.', 3, '50.000', 6, 'Tozeur oasis entrance', ['nature', 'photography']],
        ],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        foreach (self::EXPERIENCES as $host => $listings) {
            $profile = HostProfile::query()->where('display_name', $host)->first();

            if ($profile === null) {
                continue;
            }

            foreach ($listings as [$title, $category, $description, $hours, $price, $group, $meeting, $interests]) {
                Experience::updateOrCreate(
                    ['host_profile_id' => $profile->id, 'title' => $title],
                    [
                        'category' => $category, 'description' => $description, 'duration_hours' => $hours,
                        'price_per_person' => $price, 'max_group_size' => $group, 'meeting_point' => $meeting,
                        'interests' => $interests, 'photo_url' => '/images/experience-placeholder.svg', 'published' => true,
                    ],
                );
            }

            // Open every day 09:00 to 19:00 so any demo date can be booked.
            if ($profile->availabilities()->doesntExist()) {
                foreach (range(0, 6) as $day) {
                    $profile->availabilities()->create(['day_of_week' => $day, 'starts_at' => '09:00:00', 'ends_at' => '19:00:00']);
                }
            }
        }
    }
}
