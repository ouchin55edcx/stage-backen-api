<?php

namespace Database\Factories;

use App\Models\Employer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true) . ' ' . fake()->randomElement(['Server', 'Laptop', 'Desktop', 'Printer']),
            'type' => fake()->randomElement(['Server', 'Laptop', 'Desktop', 'Printer', 'Router', 'Switch']),
            'nsc' => fake()->unique()->regexify('[A-Z]{3}[0-9]{4}'),
            'status' => fake()->randomElement(['active', 'on_hold', 'in_progress']),
            'ip_address' => fake()->ipv4(),
            'serial_number' => fake()->unique()->regexify('[A-Z0-9]{10}'),
            'processor' => fake()->randomElement(['Intel i5', 'Intel i7', 'AMD Ryzen 5', 'AMD Ryzen 7', 'Intel Xeon']),
            'brand' => fake()->randomElement(['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer']),
            'office_version' => fake()->randomElement(['Office 2019', 'Office 2021', 'Office 365', 'LibreOffice']),
            'label' => fake()->words(3, true),
            'backup_enabled' => fake()->boolean(),
            'employer_id' => Employer::factory(),
        ];
    }
}
