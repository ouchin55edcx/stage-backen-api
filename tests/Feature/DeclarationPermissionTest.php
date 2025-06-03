<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Declaration;
use App\Models\Employer;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DeclarationPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a service for employers
        $this->service = Service::create([
            'name' => 'Test Service',
            'description' => 'Test service description'
        ]);
    }

    public function test_admin_can_update_declaration_status()
    {
        // Create admin user
        $adminUser = User::factory()->create([
            'role' => 'Admin',
            'email' => 'admin@test.com'
        ]);
        Admin::create(['user_id' => $adminUser->id]);

        // Create employer user
        $employerUser = User::factory()->create([
            'role' => 'Employer',
            'email' => 'employer@test.com'
        ]);
        $employer = Employer::create([
            'user_id' => $employerUser->id,
            'poste' => 'Test Position',
            'phone' => '1234567890',
            'service_id' => $this->service->id,
            'is_active' => true
        ]);

        // Create a declaration
        $declaration = Declaration::create([
            'issue_title' => 'Test Issue',
            'description' => 'Test description',
            'employer_id' => $employer->id,
            'status' => Declaration::STATUS_PENDING
        ]);

        // Admin should be able to update declaration status
        $response = $this->actingAs($adminUser, 'sanctum')
            ->putJson("/api/declarations/{$declaration->id}", [
                'status' => Declaration::STATUS_APPROVED,
                'admin_comment' => 'Approved by admin'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Declaration status has been updated to approved successfully'
            ]);

        // Verify the declaration was updated
        $declaration->refresh();
        $this->assertEquals(Declaration::STATUS_APPROVED, $declaration->status);
        $this->assertEquals('Approved by admin', $declaration->admin_comment);
    }

    public function test_admin_can_reject_declaration()
    {
        // Create admin user
        $adminUser = User::factory()->create([
            'role' => 'Admin',
            'email' => 'admin@test.com'
        ]);
        Admin::create(['user_id' => $adminUser->id]);

        // Create employer user
        $employerUser = User::factory()->create([
            'role' => 'Employer',
            'email' => 'employer@test.com'
        ]);
        $employer = Employer::create([
            'user_id' => $employerUser->id,
            'poste' => 'Test Position',
            'phone' => '1234567890',
            'service_id' => $this->service->id,
            'is_active' => true
        ]);

        // Create a declaration
        $declaration = Declaration::create([
            'issue_title' => 'Test Issue',
            'description' => 'Test description',
            'employer_id' => $employer->id,
            'status' => Declaration::STATUS_PENDING
        ]);

        // Admin should be able to reject declaration
        $response = $this->actingAs($adminUser, 'sanctum')
            ->putJson("/api/declarations/{$declaration->id}", [
                'status' => Declaration::STATUS_REJECTED,
                'admin_comment' => 'Rejected due to insufficient information'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Declaration status has been updated to rejected successfully'
            ]);

        // Verify the declaration was updated
        $declaration->refresh();
        $this->assertEquals(Declaration::STATUS_REJECTED, $declaration->status);
        $this->assertEquals('Rejected due to insufficient information', $declaration->admin_comment);
    }

    public function test_admin_can_resolve_declaration()
    {
        // Create admin user
        $adminUser = User::factory()->create([
            'role' => 'Admin',
            'email' => 'admin@test.com'
        ]);
        Admin::create(['user_id' => $adminUser->id]);

        // Create employer user
        $employerUser = User::factory()->create([
            'role' => 'Employer',
            'email' => 'employer@test.com'
        ]);
        $employer = Employer::create([
            'user_id' => $employerUser->id,
            'poste' => 'Test Position',
            'phone' => '1234567890',
            'service_id' => $this->service->id,
            'is_active' => true
        ]);

        // Create a declaration
        $declaration = Declaration::create([
            'issue_title' => 'Test Issue',
            'description' => 'Test description',
            'employer_id' => $employer->id,
            'status' => Declaration::STATUS_PENDING
        ]);

        // Admin should be able to resolve declaration using "resolved" status
        $response = $this->actingAs($adminUser, 'sanctum')
            ->putJson("/api/declarations/{$declaration->id}", [
                'status' => 'resolved',
                'admin_comment' => 'Issue has been resolved'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Declaration status has been updated to resolved successfully'
            ]);

        // Verify the declaration was updated
        $declaration->refresh();
        $this->assertEquals('resolved', $declaration->status);
        $this->assertEquals('Issue has been resolved', $declaration->admin_comment);
    }

    public function test_employer_cannot_update_declaration_status()
    {
        // Create employer user
        $employerUser = User::factory()->create([
            'role' => 'Employer',
            'email' => 'employer@test.com'
        ]);
        $employer = Employer::create([
            'user_id' => $employerUser->id,
            'poste' => 'Test Position',
            'phone' => '1234567890',
            'service_id' => $this->service->id,
            'is_active' => true
        ]);

        // Create a declaration
        $declaration = Declaration::create([
            'issue_title' => 'Test Issue',
            'description' => 'Test description',
            'employer_id' => $employer->id,
            'status' => Declaration::STATUS_PENDING
        ]);

        // Employer should not be able to update status (field will be ignored)
        $response = $this->actingAs($employerUser, 'sanctum')
            ->putJson("/api/declarations/{$declaration->id}", [
                'status' => Declaration::STATUS_APPROVED
            ]);

        $response->assertStatus(200); // Request succeeds but status field is ignored

        // Verify the declaration status was not changed
        $declaration->refresh();
        $this->assertEquals(Declaration::STATUS_PENDING, $declaration->status);
    }

    public function test_employer_can_still_update_own_declaration_content()
    {
        // Create employer user
        $employerUser = User::factory()->create([
            'role' => 'Employer',
            'email' => 'employer@test.com'
        ]);
        $employer = Employer::create([
            'user_id' => $employerUser->id,
            'poste' => 'Test Position',
            'phone' => '1234567890',
            'service_id' => $this->service->id,
            'is_active' => true
        ]);

        // Create a declaration
        $declaration = Declaration::create([
            'issue_title' => 'Original Title',
            'description' => 'Original description',
            'employer_id' => $employer->id,
            'status' => Declaration::STATUS_PENDING
        ]);

        // Employer should be able to update content
        $response = $this->actingAs($employerUser, 'sanctum')
            ->putJson("/api/declarations/{$declaration->id}", [
                'issue_title' => 'Updated Title',
                'description' => 'Updated description'
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Declaration updated successfully'
            ]);

        // Verify the declaration content was updated
        $declaration->refresh();
        $this->assertEquals('Updated Title', $declaration->issue_title);
        $this->assertEquals('Updated description', $declaration->description);
        $this->assertEquals(Declaration::STATUS_PENDING, $declaration->status); // Status should remain unchanged
    }
}
