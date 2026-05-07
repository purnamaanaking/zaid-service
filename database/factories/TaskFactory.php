<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'source_channel' => 'app_manual',
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => 'pending',
            'scheduled_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'scheduled_time' => fake()->time('H:i:s'),
            'timezone' => 'Asia/Jakarta',
            'all_day' => false,
            'is_recurring' => false,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn () => [
            'all_day' => true,
            'scheduled_time' => null,
        ]);
    }
}
