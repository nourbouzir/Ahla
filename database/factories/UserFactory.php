<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Auth\Enums\Role;
use App\Modules\Verification\Enums\HostStatus;
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
     * Define the model's default state.
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
            'phone' => null,
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
        return $this->state(fn (array $attributes) => ['role' => Role::Tourist]);
    }

    public function host(HostStatus $status = HostStatus::Pending): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Host])
            ->afterCreating(function (User $user) use ($status): void {
                $profile = $user->hostProfile()->firstOrNew();
                $profile->forceFill([
                    'status' => $status,
                    'display_name' => $user->name,
                    'bio' => '',
                    'city' => 'Tunis',
                ])->save();
            });
    }

    public function verifiedHost(): static
    {
        return $this->host(HostStatus::Verified);
    }

    public function pendingHost(): static
    {
        return $this->host(HostStatus::Pending);
    }

    public function rejectedHost(): static
    {
        return $this->host(HostStatus::Rejected);
    }

    public function suspendedHost(): static
    {
        return $this->host(HostStatus::Suspended);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => Role::Admin]);
    }
}
