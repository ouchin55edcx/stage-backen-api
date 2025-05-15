<?php

namespace Database\Seeders;

use App\Models\Employer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a service if none exists
        $service = Service::first() ?? Service::create(['name' => 'IT Department']);

        // Create a default employer user
        $user = User::create([
            'full_name' => 'Employer User',
            'email' => 'employer@example.com',
            'password' => Hash::make('password'),
            'role' => 'Employer',
        ]);

        // Create employer record
        Employer::create([
            'user_id' => $user->id,
            'poste' => 'Software Developer',
            'phone' => '123-456-7890',
            'service_id' => $service->id,
            'is_active' => true,
        ]);

        // Create some additional employers with factory
        $services = Service::factory(3)->create();
        
        foreach ($services as $service) {
            $user = User::factory()->employer()->create();
            
            Employer::create([
                'user_id' => $user->id,
                'poste' => fake()->jobTitle(),
                'phone' => fake()->phoneNumber(),
                'service_id' => $service->id,
                'is_active' => true,
            ]);
        }
    }
}
