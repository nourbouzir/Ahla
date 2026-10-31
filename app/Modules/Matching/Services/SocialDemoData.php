<?php

namespace App\Modules\Matching\Services;

class SocialDemoData
{
    public function locals(): array
    {
        return [
            [
                'id' => 'yasmine',
                'name' => 'Yasmine Ben Amor',
                'age' => 22,
                'city' => 'Tunis',
                'role' => 'Design student',
                'photo' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=1100&q=88',
                'bio' => 'I love showing visitors the little cafés, rooftops and side streets that make Tunis feel like home.',
                'interests' => ['Photography', 'Art', 'Food'],
                'languages' => ['Arabic', 'French', 'English'],
                'experience' => 'A photo walk through the medina',
                'distance' => '2 km away',
                'online' => true,
                'tagline' => 'Good light, strong coffee, no plans.',
            ],
            [
                'id' => 'sami',
                'name' => 'Sami Gharbi',
                'age' => 21,
                'city' => 'Tunis',
                'role' => 'Film student',
                'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=1100&q=88',
                'bio' => 'Always looking for a good frame, a better sandwich, or someone to argue about old Tunisian films with.',
                'interests' => ['Film', 'Street food', 'Football'],
                'languages' => ['Arabic', 'French', 'English'],
                'experience' => 'The best bambalouni in town',
                'distance' => '4 km away',
                'online' => true,
                'tagline' => 'Director of questionable snack decisions.',
            ],
            [
                'id' => 'mariam',
                'name' => 'Mariam Trabelsi',
                'age' => 24,
                'city' => 'Sousse',
                'role' => 'Music teacher',
                'photo' => 'https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=1100&q=88',
                'bio' => 'A little obsessed with live music and the old town. I know where to find the sunset when the beach is too busy.',
                'interests' => ['Music', 'The sea', 'History'],
                'languages' => ['Arabic', 'French', 'English'],
                'experience' => 'Sousse after golden hour',
                'distance' => 'Sousse local',
                'online' => false,
                'tagline' => 'Somewhere between a song and the sea.',
            ],
            [
                'id' => 'nour',
                'name' => 'Nour Ben Salem',
                'age' => 23,
                'city' => 'Djerba',
                'role' => 'Textile artist',
                'photo' => 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=1100&q=88',
                'bio' => 'My family has woven here for generations. Come for the colours, stay for the stories (and the fresh bambalouni).',
                'interests' => ['Textiles', 'Culture', 'Photography'],
                'languages' => ['Arabic', 'French', 'English'],
                'experience' => 'A morning in my weaving studio',
                'distance' => 'Djerba local',
                'online' => true,
                'tagline' => 'Making things slowly, under the sun.',
            ],
            [
                'id' => 'rami',
                'name' => 'Rami Jebali',
                'age' => 26,
                'city' => 'Tozeur',
                'role' => 'Oasis guide',
                'photo' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=1100&q=88',
                'bio' => 'Born at the edge of the oasis. I know the palms, the old brickwork and the quiet places worth the early alarm.',
                'interests' => ['Nature', 'Hiking', 'Architecture'],
                'languages' => ['Arabic', 'French', 'English'],
                'experience' => 'Sunrise between the palm groves',
                'distance' => 'Tozeur local',
                'online' => true,
                'tagline' => 'Up before the heat. Always worth it.',
            ],
            [
                'id' => 'salma',
                'name' => 'Salma Kefi',
                'age' => 25,
                'city' => 'Sfax',
                'role' => 'Ceramic artist',
                'photo' => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?auto=format&fit=crop&w=1100&q=88',
                'bio' => 'I make little things from local clay and big plans around food. Sfax is quieter than people expect—in the best way.',
                'interests' => ['Ceramics', 'Food', 'Design'],
                'languages' => ['Arabic', 'French', 'English'],
                'experience' => 'Clay, coffee & the old port',
                'distance' => 'Sfax local',
                'online' => false,
                'tagline' => 'A tiny studio and a very big lunch.',
            ],
        ];
    }

    public function places(): array
    {
        return [
            ['city' => 'Tunis', 'locals' => 12, 'experiences' => 5, 'longitude' => 10.1815, 'latitude' => 36.8065, 'note' => 'Medina mornings & café corners'],
            ['city' => 'Sidi Bou Said', 'locals' => 8, 'experiences' => 3, 'longitude' => 10.3470, 'latitude' => 36.8700, 'note' => 'Blue doors & sea views'],
            ['city' => 'Hammamet', 'locals' => 7, 'experiences' => 4, 'longitude' => 10.6167, 'latitude' => 36.4000, 'note' => 'Garden paths & salt air'],
            ['city' => 'Sousse', 'locals' => 8, 'experiences' => 3, 'longitude' => 10.6367, 'latitude' => 35.8256, 'note' => 'The old town after dark'],
            ['city' => 'Kairouan', 'locals' => 5, 'experiences' => 2, 'longitude' => 10.0963, 'latitude' => 35.6781, 'note' => 'Quiet courtyards & warm makroudh'],
            ['city' => 'Sfax', 'locals' => 5, 'experiences' => 3, 'longitude' => 10.7603, 'latitude' => 34.7406, 'note' => 'Market days & island ferries'],
            ['city' => 'Djerba', 'locals' => 6, 'experiences' => 4, 'longitude' => 10.8590, 'latitude' => 33.8750, 'note' => 'Whitewashed lanes & weaving'],
            ['city' => 'Tozeur', 'locals' => 4, 'experiences' => 2, 'longitude' => 8.1335, 'latitude' => 33.9197, 'note' => 'Oasis walks at first light'],
        ];
    }

    public function conversations(): array
    {
        return [
            [
                'id' => 'rami',
                'preview' => 'That little café is even better at sunset.',
                'time' => '10:42',
                'unread' => 1,
                'messages' => [
                    ['from' => 'them', 'text' => 'Ahla! So glad we connected 🌴', 'time' => '10:31'],
                    ['from' => 'me', 'text' => 'Your oasis photos made me add Tozeur to my trip.', 'time' => '10:34'],
                    ['from' => 'them', 'text' => 'You have to come in the morning. I know a little café by the old brick lanes.', 'time' => '10:38'],
                    ['from' => 'them', 'text' => 'That little café is even better at sunset.', 'time' => '10:42'],
                ],
            ],
        ];
    }
}
