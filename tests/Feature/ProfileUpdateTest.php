<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Employer;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $employerUser;
    protected $adminToken;
    protected $employerToken;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a service
        $this->service = Service::factory()->create(['name' => 'IT Department']);

        // Create an admin user
        $this->adminUser = User::factory()->create([
            'full_name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'Admin',
        ]);
        $this->adminUser->admin()->create();

        // Create an employer user
        $this->employerUser = User::factory()->create([
            'full_name' => 'Employer User',
            'email' => 'employer@example.com',
            'role' => 'Employer',
        ]);
        Employer::factory()->create([
            'user_id' => $this->employerUser->id,
            'service_id' => $this->service->id,
            'poste' => 'Developer',
            'phone' => '123-456-7890',
        ]);

        // Generate tokens
        $this->adminToken = $this->adminUser->createToken('test-token')->plainTextToken;
        $this->employerToken = $this->employerUser->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_returns_detailed_user_profile_for_employer()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employerToken,
        ])->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'full_name',
                    'email',
                    'role',
                    'profile' => [
                        'poste',
                        'phone',
                        'service_id',
                        'service_name',
                        'is_active',
                    ],
                ],
            ]);
    }

    /** @test */
    public function employer_can_update_their_profile()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employerToken,
        ])->putJson('/api/profile', [
            'full_name' => 'Updated Employer Name',
            'poste' => 'Senior Developer',
            'phone' => '987-654-3210',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.full_name', 'Updated Employer Name')
            ->assertJsonPath('user.profile.poste', 'Senior Developer')
            ->assertJsonPath('user.profile.phone', '987-654-3210');

        $this->assertDatabaseHas('users', [
            'id' => $this->employerUser->id,
            'full_name' => 'Updated Employer Name',
        ]);

        $this->assertDatabaseHas('employers', [
            'user_id' => $this->employerUser->id,
            'poste' => 'Senior Developer',
            'phone' => '987-654-3210',
        ]);
    }

    /** @test */
    public function admin_can_update_their_profile()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/profile', [
            'full_name' => 'Updated Admin Name',
            'email' => 'updated.admin@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('user.full_name', 'Updated Admin Name')
            ->assertJsonPath('user.email', 'updated.admin@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $this->adminUser->id,
            'full_name' => 'Updated Admin Name',
            'email' => 'updated.admin@example.com',
        ]);
    }

    /** @test */
    public function it_validates_profile_update_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employerToken,
        ])->putJson('/api/profile', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}
