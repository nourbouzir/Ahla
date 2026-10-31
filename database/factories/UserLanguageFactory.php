<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Auth\Models\UserLanguage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserLanguage>
 */
class UserLanguageFactory extends Factory
{
    protected $model = UserLanguage::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'language' => fake()->randomElement(['en', 'fr', 'ar', 'it']),
        ];
    }
}
