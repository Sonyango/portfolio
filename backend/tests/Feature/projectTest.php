<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class projectTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);
    }

    private function authHeaders(): array
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    private function createProject(array $overrides = []): Project
    {
        return Project::create(array_merge([
            'title' => 'Test Project',
            'slug' => 'test-project',
            'description' => 'A test project description.',
            'tech_stack' => ['Laravel', 'Vue.js'],
            'published' => true,
            'featured' => false,
            'order' => 0,
        ], $overrides));
    }

    // Public endpoints

    public function test_public_can_list_published_projects(): void
    {
        $this->createProject(['published' => true]);
        $this->createProject([
            'title' => 'Draft Project',
            'slug' => 'draft-project',
            'published' => false,
        ]);

        $response = $this->getJson('/api/projects');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_public_can_view_single_project(): void
    {
        $this->createProject();

        $response = $this->getJson('/api/projects/test-project');
        $response->assertStatus(200)
            ->assertJsonPath('data.slug', 'test-project')
            ->assertJsonPath('data.title', 'Test Project');
    }

    public function test_public_gets_404_for_nonexistent_project(): void
    {
        $response = $this->getJson('/api/projects/nonexistent');
        $response->assertStatus(404);
    }

    // Admin endpoints

    public function test_admin_can_list_all_projects(): void
    {
        $this->createProject(['published' => true]);
        $this->createProject([
            'title' => 'Draft',
            'slug' => 'draft',
            'published' => false,
        ]);
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/admin/projects');
        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_create_project(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/projects', [
                'title' => 'New Project',
                'slug' => 'new-project',
                'description' => 'Description here.',
                'published' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Project');

        $this->assertDatabaseHas('projects', ['slug' => 'new-project']);
    }

    public function test_admin_can_update_project(): void
    {
        $project = $this->createProject();

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/admin/projects/{$project->id}", [
                'title' => 'Updated Title',
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');
    }

    public function test_admin_can_delete_project(): void
    {
        $project = $this->createProject();
        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/admin/projects/{$project->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_unauthenticated_cannot_access_admin_projects(): void
    {
        $response = $this->getJson('/api/admin/projects');
        $response->assertStatus(401);
    }

    public function test_project_creation_requires_title_and_description(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/admin/projects', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }
}
