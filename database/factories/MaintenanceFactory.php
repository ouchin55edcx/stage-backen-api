<?php

namespace Database\Factories;

use App\Models\Intervention;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Maintenance>
 */
class MaintenanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'intervention_id' => Intervention::factory(),
            'maintenance_type' => fake()->randomElement(['Preventive', 'Corrective', 'Predictive']),
            'scheduled_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
            'performed_date' => fake()->optional(0.7)->dateTimeBetween('-1 month', 'now'),
            'next_maintenance_date' => fake()->optional(0.8)->dateTimeBetween('now', '+6 months'),
            'observations' => fake()->optional(0.6)->sentence(),
        ];
    }
}
