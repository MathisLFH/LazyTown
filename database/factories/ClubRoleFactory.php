<?php

namespace Database\Factories;

use App\Models\ClubRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClubRole>
 */
class ClubRoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => null,
            'name' => fake()->unique()->jobTitle(),
            'guard_name' => 'web',
        ];
    }
}
