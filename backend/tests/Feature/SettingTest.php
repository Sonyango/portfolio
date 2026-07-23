<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private function authHeaders(): array
    {
        $admin = User::create([
            'name'      => 'Admin',
            'email'     => 'admin@test.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
        ]);
        $token = $admin->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_public_can_fetch_settings(): void
    {
        Setting::create(['key' => 'site_name', 'value' => 'My Portfolio', 'group' => 'general']);

        $response = $this->getJson('/api/settings');
        $response->assertStatus(200)
                ->assertJsonPath('data.site_name', 'My Portfolio');
    }

    public function test_admin_can_update_settings(): void
    {
        Setting::create(['key' => 'site_name', 'value' => 'Old Name', 'group' => 'general']);

        $response = $this->withHeaders($this->authHeaders())
                        ->putJson('/api/admin/settings', [
                            'settings' => [
                                ['key' => 'site_name', 'value' => 'New Name', 'group' => 'general'],
                            ]
                        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('settings', [
            'key'   => 'site_name',
            'value' => 'New Name',
        ]);
    }

    public function test_unauthenticated_cannot_update_settings(): void
    {
        $response = $this->putJson('/api/admin/settings', [
            'settings' => [
                ['key' => 'site_name', 'value' => 'Hacked', 'group' => 'general'],
            ]
        ]);

        $response->assertStatus(401);
    }
}
