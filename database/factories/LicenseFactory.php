<?php

namespace Database\Factories;

use App\Models\Equipment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\License>
 */
class LicenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Windows 10 Pro', 'Office 365', 'Adobe Creative Suite', 'Antivirus Premium', 'SQL Server']),
            'type' => fake()->randomElement(['Operating System', 'Office Suite', 'Security', 'Database', 'Design Software']),
            'key' => fake()->regexify('[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}'),
            'expiration_date' => fake()->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'equipment_id' => Equipment::factory(),
        ];
    }
}
