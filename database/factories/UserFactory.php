<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'google_subject' => (string) Str::uuid(),
            'email' => fake()->unique()->safeEmail(),
            'full_name' => fake()->name(),
            'avatar_url' => fake()->imageUrl(),
            'status' => UserStatus::Provisional->value,
            'phone_verified_at' => null,
            'onboarded_at' => null,
            'last_active_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Active->value,
            'phone_verified_at' => now(),
            'onboarded_at' => now(),
        ]);
    }
}
