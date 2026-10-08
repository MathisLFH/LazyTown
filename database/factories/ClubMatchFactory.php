<?php

namespace Database\Factories;

use App\Models\ClubMatch;
use App\Models\Team;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubMatch>
 */
class ClubMatchFactory extends Factory
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
            'season' => '2026/2027',
            'date' => fake()->date(),
            'versus' => fake()->company(),
            'at_home' => fake()->boolean(),
            'city' => fake()->city(),
        ];
    }
}
