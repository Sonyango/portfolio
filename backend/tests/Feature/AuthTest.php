<?php

namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name'      => 'Admin',
            'email'     => 'admin@test.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
        ]);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $this->createAdmin();

        $response = $this->postJson('/api/admin/login', [
            'email'     => 'admin@test.com',
            'password'  => 'password123',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'message',
                    'token',
                    'user' => ['id', 'name', 'email', 'role'],
                ]);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $this->createAdmin();

        $response = $this->postJson('/api/admin/login', [
            'email'     => 'admin@test.com',
            'password'  => 'wrongpassword',
        ]);

        $response->assertStatus(401)
                ->assertJson(['message' => 'Invalid credentials.']);
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/admin/login', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_access_me_endpoint(): void
    {
        $admin  = $this->createAdmin();
        $token  = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
                        ->getJson('/api/admin/me');

        $response->assertStatus(200)
                ->assertJsonPath('user.email', 'admin@test.com');
    }

    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $response   = $this->getJson('/api/admin/me');
        $response->assertStatus(401);
    }

    public function test_admin_can_logout(): void
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
                        ->postJson('/api/admin/logout');

        $response->assertStatus(200)
                ->assertJson(['message' => 'Logged out successfully.']);
    }
}
