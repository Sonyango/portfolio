<?php

namespace Tests\Feature;
use App\Models\User;
use App\Models\MfaPending;
use App\Services\BrevoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
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

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $this->createAdmin();

        $response = $this->postJson('/api/admin/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
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
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
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
        $admin = $this->createAdmin();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/admin/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', 'admin@test.com');
    }

    public function test_unauthenticated_user_cannot_access_me_endpoint(): void
    {
        $response = $this->getJson('/api/admin/me');
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

    public function test_idle_token_is_revoked_after_fifteen_minutes(): void
    {
        $admin = $this->createAdmin();
        $newToken = $admin->createToken('test-token');
        $token = $newToken->accessToken;

        DB::table('personal_access_tokens')
            ->where('id', $token->id)
            ->update(['last_activity_at' => now()->subMinutes(16)]);

        $this->withToken($newToken->plainTextToken)
            ->getJson('/api/admin/me')
            ->assertUnauthorized()
            ->assertJsonPath('session_expired', true);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    public function test_activity_check_refreshes_token_activity(): void
    {
        $this->mock(BrevoService::class);
        $admin = $this->createAdmin();
        $newToken = $admin->createToken('test-token');
        $previousActivity = now()->subMinutes(2)->startOfSecond();

        DB::table('personal_access_tokens')
            ->where('id', $newToken->accessToken->id)
            ->update(['last_activity_at' => $previousActivity]);

        $this->withToken($newToken->plainTextToken)
            ->postJson('/api/admin/activity-check')
            ->assertOk()
            ->assertJsonPath('active', true);

        $updatedActivity = Carbon::parse(
            DB::table('personal_access_tokens')
                ->where('id', $newToken->accessToken->id)
                ->value('last_activity_at')
        );

        $this->assertTrue($updatedActivity->greaterThan($previousActivity));
    }

    public function test_regular_api_request_does_not_refresh_token_activity(): void
    {
        $this->mock(BrevoService::class);
        $admin = $this->createAdmin();
        $newToken = $admin->createToken('test-token');
        $previousActivity = now()->subMinutes(2)->startOfSecond();

        DB::table('personal_access_tokens')
            ->where('id', $newToken->accessToken->id)
            ->update(['last_activity_at' => $previousActivity]);

        $this->withToken($newToken->plainTextToken)
            ->getJson('/api/admin/me')
            ->assertOk();

        $storedActivity = Carbon::parse(
            DB::table('personal_access_tokens')
                ->where('id', $newToken->accessToken->id)
                ->value('last_activity_at')
        );

        $this->assertTrue($storedActivity->equalTo($previousActivity));
    }

    public function test_mfa_login_initializes_token_activity_timestamp(): void
    {
        $this->mock(BrevoService::class)
            ->shouldReceive('sendLoginAlert')
            ->once()
            ->andReturn(true);

        $admin = $this->createAdmin();
        $pending = MfaPending::create([
            'user_id' => $admin->id,
            'token' => str_repeat('a', 64),
            'method' => 'email',
            'email_code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/admin/mfa/verify', [
            'mfa_token' => $pending->token,
            'code' => '123456',
        ])->assertOk();

        $this->assertNotNull(
            DB::table('personal_access_tokens')->value('last_activity_at')
        );
    }
}
