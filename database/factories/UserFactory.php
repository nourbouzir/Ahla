<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Auth\Enums\HostStatus;
use App\Modules\Auth\Enums\Role;
use App\Modules\Auth\Models\HostProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state: a verified-email tourist.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => Role::Tourist,
            'phone' => fake()->numerify('+216 ## ### ###'),
            'preferred_language' => 'en',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function tourist(): static
    {
        return $this->state(['role' => Role::Tourist]);
    }

    public function admin(): static
    {
        return $this->state(['role' => Role::Admin]);
    }

    /** A host together with a host profile in the given status (pending unless told otherwise). */
    public function host(HostStatus $status = HostStatus::Pending): static
    {
        return $this->state(['role' => Role::Host])
            ->has(HostProfile::factory()->state(['status' => $status]), 'hostProfile');
    }

    public function pendingHost(): static
    {
        return $this->host(HostStatus::Pending);
    }

    public function verifiedHost(): static
    {
        return $this->host(HostStatus::Verified);
    }

    public function rejectedHost(): static
    {
        return $this->host(HostStatus::Rejected);
    }

    public function suspendedHost(): static
    {
        return $this->host(HostStatus::Suspended);
    }

    /**
     * Languages the user speaks, e.g. ->withLanguages(['fr', 'ar']).
     *
     * @param  list<string>  $codes
     */
    public function withLanguages(array $codes): static
    {
        return $this->afterCreating(function (User $user) use ($codes) {
            $user->languages()->createMany(array_map(fn (string $code) => ['language' => $code], $codes));
        });
    }

    /**
     * Interests (host) or preferences (tourist), e.g. ->withInterests(['food', 'crafts']).
     *
     * @param  list<string>  $interests
     */
    public function withInterests(array $interests): static
    {
        return $this->afterCreating(function (User $user) use ($interests) {
            $user->interests()->createMany(array_map(fn (string $interest) => ['interest' => $interest], $interests));
        });
    }
}
