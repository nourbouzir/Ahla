<?php

namespace App\Modules\Verification\Database\Seeders;

use App\Models\User;
use App\Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use App\Modules\Auth\Enums\Role;
use App\Modules\Verification\Enums\HostStatus;
use App\Modules\Verification\Models\HostProfile;
use App\Modules\Verification\Models\VerificationRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Demo hosts across the main cities and languages (not seeded in production):
 * ten verified, one pending with an application waiting for the admin, one rejected.
 * All demo accounts use the password in AuthDatabaseSeeder::DEMO_PASSWORD.
 */
class VerificationDatabaseSeeder extends Seeder
{
    /** name, email, city, languages, interests, rating, status, bio */
    private const HOSTS = [
        ['Amel Ben Youssef', 'amel@ahla.test', 'Tunis', ['ar', 'fr', 'en'], ['history', 'craft', 'food'], 4.9, 'verified', 'Medina-born storyteller who knows which doors open onto the best courtyards and workshops.'],
        ['Youssef Trabelsi', 'youssef@ahla.test', 'Sidi Bou Said', ['ar', 'fr', 'en'], ['art', 'photography', 'history'], 4.8, 'verified', 'Painter and lifelong local. I love the village most when the day-trippers have gone home.'],
        ['Ines Gharbi', 'ines@ahla.test', 'La Marsa', ['ar', 'fr', 'en'], ['craft', 'nature', 'art'], 4.7, 'verified', 'Ceramic artist with a sunny studio by the sea. Come and get clay under your nails.'],
        ['Mehdi Louati', 'mehdi@ahla.test', 'Tunis', ['ar', 'fr'], ['food', 'history'], null, 'verified', 'History teacher by day, passionate cook by night. New on Ahla.'],
        ['Sana Mejri', 'sana@ahla.test', 'Sousse', ['ar', 'fr', 'en'], ['history', 'food'], 4.6, 'verified', 'Sousse guide who loves the old town after dark and the best bambalouni in the city.'],
        ['Karim Haddad', 'karim@ahla.test', 'Hammamet', ['fr', 'en'], ['nature', 'photography'], 4.4, 'verified', 'Photographer who knows the quiet coves and the best light on the Cap Bon coast.'],
        ['Rania Zouari', 'rania@ahla.test', 'Kairouan', ['ar', 'fr'], ['craft', 'history'], 4.9, 'verified', 'Third-generation carpet weaver. Kairouan is slower than you expect, in the best way.'],
        ['Walid Chebbi', 'walid@ahla.test', 'Sfax', ['ar', 'fr'], ['food', 'craft'], null, 'verified', 'Fisherman’s son who cooks what the port brings in. New on Ahla.'],
        ['Leila Ben Salah', 'leila@ahla.test', 'Djerba', ['ar', 'fr', 'en'], ['craft', 'art', 'nature'], 4.8, 'verified', 'Weaver and potter from Djerba. Whitewashed lanes, island markets and slow afternoons.'],
        ['Hichem Dridi', 'hichem@ahla.test', 'Tozeur', ['ar', 'fr', 'en'], ['nature', 'photography'], 4.5, 'verified', 'Oasis guide. I know the palms, the old brickwork and the quiet places worth an early alarm.'],
        ['Omar Kefi', 'omar@ahla.test', 'Tunis', ['ar', 'en'], ['food'], null, 'pending', 'Street-food fan waiting for verification.'],
        ['Nadia Feki', 'nadia@ahla.test', 'Sousse', ['ar', 'fr'], ['art'], null, 'rejected', 'Application rejected: identity document was not readable.'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $accounts = new AuthDatabaseSeeder;

        foreach (self::HOSTS as [$name, $email, $city, $languages, $interests, $rating, $status, $bio]) {
            $user = $accounts->account($name, $email, Role::Host, AuthDatabaseSeeder::DEMO_PASSWORD, $languages[0] === 'ar' ? 'fr' : $languages[0], $languages, $interests);
            [$lat, $lng] = config('ahla.cities')[$city];

            $profile = HostProfile::firstOrNew(['user_id' => $user->id]);
            $profile->forceFill([
                'display_name' => $name, 'bio' => $bio, 'city' => $city, 'latitude' => $lat, 'longitude' => $lng,
                'rating_avg' => $rating, 'status' => HostStatus::from($status),
            ])->save();

            if (in_array($status, ['pending', 'rejected'], true)) {
                $this->application($profile, $status);
            }
        }
    }

    /** An application with a tiny encrypted placeholder document, so the admin screens have something to show. */
    private function application(HostProfile $profile, string $status): void
    {
        if ($profile->verificationRequests()->exists()) {
            return;
        }

        $path = 'verification/'.$profile->id.'/demo-id.png.enc';
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
        Storage::disk('local')->put($path, Crypt::encryptString($png));

        VerificationRequest::forceCreate($status === 'pending'
            ? ['host_profile_id' => $profile->id, 'evidence_path' => $path, 'status' => 'submitted']
            : ['host_profile_id' => $profile->id, 'evidence_path' => $path, 'status' => 'decided', 'decision' => 'rejected',
                'decision_reason' => 'The identity document was not readable.', 'decided_at' => now()->subDays(3)]);
    }
}
