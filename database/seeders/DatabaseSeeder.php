<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the AdminSeeder to create the default admin user
        $this->call([
            AdminSeeder::class,
        ]);

        // Create some employer users
        User::factory(5)->employer()->create();
    }
}
