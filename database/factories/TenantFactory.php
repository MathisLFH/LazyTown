<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();
        $subdomainWords = fake()->unique()->words(2, true);

        return [
            'admin_id' => null,
            'subdomain' => Str::slug(is_string($subdomainWords) ? $subdomainWords : implode(' ', $subdomainWords)),
            'name' => $name,
        ];
    }
}
