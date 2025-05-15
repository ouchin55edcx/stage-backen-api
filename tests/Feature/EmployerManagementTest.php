<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Employer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $adminToken;
    protected $employerUser;
    protected $employerToken;
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a service
        $this->service = Service::factory()->create();

        // Create an admin user
        $this->adminUser = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role' => 'Admin',
        ]);

        Admin::factory()->create([
            'user_id' => $this->adminUser->id,
        ]);

        // Create an employer user
        $this->employerUser = User::factory()->create([
            'email' => 'employer@test.com',
            'password' => Hash::make('password'),
            'role' => 'Employer',
        ]);

        Employer::factory()->create([
            'user_id' => $this->employerUser->id,
            'service_id' => $this->service->id,
        ]);

        // Get tokens
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);
        $this->adminToken = $response->json('token');

        $response = $this->postJson('/api/login', [
            'email' => 'employer@test.com',
            'password' => 'password',
        ]);
        $this->employerToken = $response->json('token');
    }

    /** @test */
    public function admin_can_view_all_employers()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/employers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'employers' => [
                    '*' => [
                        'id',
                        'user_id',
                        'full_name',
                        'email',
                        'poste',
                        'phone',
                        'service',
                        'service_id',
                        'is_active',
                        'created_at',
                    ],
                ],
            ]);
    }

    /** @test */
    public function admin_can_create_an_employer()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/employers', [
            'full_name' => 'New Employer',
            'email' => 'new.employer@test.com',
            'poste' => 'Developer',
            'phone' => '123-456-7890',
            'service_id' => $this->service->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'employer' => [
                    'id',
                    'user_id',
                    'full_name',
                    'email',
                    'poste',
                    'phone',
                    'service',
                    'service_id',
                    'is_active',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'new.employer@test.com',
            'role' => 'Employer',
        ]);

        $this->assertDatabaseHas('employers', [
            'poste' => 'Developer',
            'phone' => '123-456-7890',
            'is_active' => 1,
        ]);
    }

    /** @test */
    public function admin_can_update_an_employer()
    {
        $employer = Employer::where('user_id', $this->employerUser->id)->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson('/api/employers/' . $employer->id, [
            'full_name' => 'Updated Name',
            'poste' => 'Senior Developer',
            'phone' => '987-654-3210',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'employer' => [
                    'id',
                    'user_id',
                    'full_name',
                    'email',
                    'poste',
                    'phone',
                    'service',
                    'service_id',
                    'is_active',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->employerUser->id,
            'full_name' => 'Updated Name',
        ]);

        $this->assertDatabaseHas('employers', [
            'id' => $employer->id,
            'poste' => 'Senior Developer',
            'phone' => '987-654-3210',
        ]);
    }

    /** @test */
    public function admin_can_toggle_employer_active_status()
    {
        $employer = Employer::where('user_id', $this->employerUser->id)->first();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->patchJson('/api/employers/' . $employer->id . '/toggle-active');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'is_active',
            ]);

        $this->assertDatabaseHas('employers', [
            'id' => $employer->id,
            'is_active' => !$employer->is_active,
        ]);
    }

    /** @test */
    public function inactive_employer_cannot_login()
    {
        // First deactivate the employer
        $employer = Employer::where('user_id', $this->employerUser->id)->first();
        $employer->is_active = false;
        $employer->save();

        // Try to login
        $response = $this->postJson('/api/login', [
            'email' => 'employer@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Your account has been deactivated. Please contact the administrator.',
            ]);
    }

    /** @test */
    public function non_admin_cannot_access_employer_management()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->employerToken,
        ])->getJson('/api/employers');

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_search_employers_by_name()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/employers/search', [
            'name' => substr($this->employerUser->full_name, 0, 3),
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'employers' => [
                    '*' => [
                        'id',
                        'user_id',
                        'full_name',
                        'email',
                        'poste',
                        'phone',
                        'service',
                        'service_id',
                        'is_active',
                        'created_at',
                    ],
                ],
            ]);
    }
}
