<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Team;
use App\Models\Tenant;
use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Training>
 */
class TrainingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'team_id' => Team::factory(),
            'gym_id' => Gym::factory(),
            'weekday' => fake()->randomElement(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']),
            'starts_at' => '18:00:00',
            'ends_at' => '19:30:00',
        ];
    }
}
