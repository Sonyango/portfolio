<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function createAdmin(): User
    {
        return User::create([
            'name'      => 'Admin',
            'email'     => 'admin@test.com',
            'password'  => Hash::make('password123'),
            'role'      => 'admin',
        ]);
    }

    private function authHeaders(): array
    {
        $admin = $this->createAdmin();
        $token = $admin->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    // Upload tests
    public function test_admin_can_upload_profile_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('profile.jpg', 200, 200);

        $response = $this->withHeaders($this->authHeaders())
                        ->post('/api/admin/profile-image', [
                            'profile_image' => $file,
                        ]);
        $response->assertStatus(200)
            ->assertjsonStructure(['message', 'url', 'path'])
            ->assertJsonPath('message', 'Profile image uploaded successfully.');

        // Confirm saved to settings
        $this->assertDatabaseHas('settings', [
            'key' => 'profile_image',
        ]);

        // Confirm path is not empty
        $setting = Setting::where('key', 'profile_image')->first();
        $this->assertNotEmpty($setting->value);

        // Confirm file exists in storage
        Storage::disk('public')->assertExists($setting->value);
        //$this->assertTrue(Storage::disk('public')->exists($setting->value));
    }

    public function test_upload_requires_an_image_file(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');
        $response = $this->withHeaders($this->authHeaders())
                        ->postJson('/api/admin/profile-image', [
                            'profile_image' => $file,
                        ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['profile_image']);
    }

    public function test_upload_rejects_files_larger_than_2mb(): void
    {
        //Create a fake image larger than 2mb
        $file = UploadedFile::fake()->image('large.jpg')->size(3000);
        $response = $this->withHeaders($this->authHeaders())
                            ->postJson('/api/admin/profile-image', [
                                'profile_image' => $file,
                            ]);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['profile_image']);
    }

    public function test_upload_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('profile.jpg');
        $response = $this->postJson('/api/admin/profile-image', [
            'profile_image' => $file,
        ]);
        $response->assertStatus(401);
    }

    public function test_upload_requires_image_field(): void
    {
        $response = $this->withHeaders($this->authHeaders())
                        ->postJson('/api/admin/profile-image', []);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['profile_image']);
    }

    public function test_uploading_new_image_replaces_old_one(): void
    {
        Storage::fake('public');
        //Upload first image
        $firstFile = UploadedFile::fake()->image('first.jpg', 100, 100);
        $this->withHeaders($this->authHeaders())
            ->post('/api/admin/profile-image', [
                'profile_image' => $firstFile,
            ]);

        $firstPath = Setting::where('key', 'profile_image')->value('value');

        // Upload second image
        $admin = User::first();
        $token = $admin->createToken('test2')->plainTextToken;
        $secondFile = UploadedFile::fake()->image('second.jpg', 100, 100);

        $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->post('/api/admin/profile-image', [
                'profile_image' => $secondFile,
            ]);

        $secondPath = Setting::where('key', 'profile_image')->value('value');

        // Paths should be different
        $this->assertNotEquals($firstPath, $secondPath);

        // New file should exist
        Storage::disk('public')->assertExists($secondPath);
        //$this->assertTrue(Storage::disk('public')->exists($secondPath));

    }

    public function test_accepted_image_formats(): void
    {
        $headers = $this->authHeaders();
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $format) {
            $file = UploadedFile::fake()->image("profile.{$format}");

            $response = $this->withHeaders($headers)
                            ->post('/api/admin/profile-image', [
                                'profile_image' => $file,
                            ]);
            $response->assertStatus(200);
        }
    }

    // Delete tests
    public function test_admin_can_delete_profile_image(): void
    {
        Storage::fake('public');

        // Upload an image first
        $file = UploadedFile::fake()->image('profile.jpg');
        $this->withHeaders($this->authHeaders())
            ->post('/api/admin/profile-image', [
                'profile_image' => $file,
            ]);
        $path = Setting::where('key', 'profile_image')->value('value');

        Storage::disk('public')->assertExists($path);

        // Now delete it
        $admin = User::first();
        $token = $admin->createToken('delete-test')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}"
        ])->deleteJson('/api/admin/profile-image');
        $response->assertStatus(200)
                ->assertJson(['message' => 'Profile image removed successfully.']);

        // Confirm file deleted from storage
        Storage::disk('public')->assertMissing($path);

        //Confirm setting cleared
        $setting = Setting::where('key', 'profile_image')->first();
        $this->assertEquals('', $setting->value ?? '');

    }

    public function test_delete_requires_authentication(): void
    {
        $response = $this->deleteJson('/api/admin/profile-image');
        $response->assertStatus(401);
    }

    // Protected route tests
    public function test_profile_image_served_through_protected_route(): void
    {
        // Upload image and set in settings

        $file = UploadedFile::fake()->image('profile.jpg', 100, 100);

        Storage::disk('public')->put(
            'profile/test.jpg',
            file_get_contents($file->getRealPath())
        );

        Setting::set('profile_image', 'profile/test.jpg', 'general');

        $response = $this->get('/api/profile-image');

        $response->assertStatus(200);

        // Check Content-Type starts with image/
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotEmpty($contentType, 'Content-Type header should not be empty');
        $this->assertStringStartsWith('image/', $contentType);
    }

    public function test_protected_route_returns_404_when_no_image_set(): void
    {
        // Ensure no profile image in settings
        Setting::where('key', 'profile_image')->delete();

        $response = $this->get('/profile-image');
        $response->assertStatus(404);
    }

    public function test_protected_routes_404_when_image_empty(): void
    {
        Setting::set('profile_image', '', 'general');

        $response = $this->get('/profile-image');
        $response->assertStatus(404);
    }

    public function test_protected_route_has_security_headers(): void
    {
        $file = UploadedFile::fake()->image('profile.jpg', 100, 100);
        Storage::disk('public')->put(
            'profile/test.jpg',
            file_get_contents($file->getRealPath())
        );
        Setting::set('profile_image', 'profile/test.jpg', 'general');

        $response = $this->get('/api/profile-image');

        $response->assertStatus(200);
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Content-Disposition', 'inline');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    // Public settings API tets
    public function test_public_settings_returns_protected_url_for_portfolio_image(): void
    {
        Setting::set('profile_image', 'profile/test.jpg', 'general');
        $response = $this->getJson('/api/settings');
        $response->assertStatus(200);

        $profileImageUrl = $response->json('data.profile_image');

        // Should return the protected route URL, not the storage path
        $this->assertStringContainsString('/profile-image', $profileImageUrl);
        $this->assertStringNotContainsString('/storage/', $profileImageUrl);
        $this->assertStringNotContainsString('profile/test.jpg', $profileImageUrl);
    }

    public function test_public_settings_returns_empty_when_no_profile_image(): void
    {
        Setting::where('key', 'profile_image')->delete();

        $response = $this->getJson('/api/settings');
        $response->assertStatus(200);

        $profileImage = $response->json('data.profile_image');
        $this->assertTrue(
            $profileImage === '' || $profileImage === null,
            'Profile image should be empty when not set'
        );
    }

    // Admin settings API tests
    public function test_admin_settings_returns_protected_url_for_profile_image(): void
    {
        Setting::set('profile_image', 'profile/test.jpg', 'general');
        $response = $this->withHeaders($this->authHeaders())
                        ->getJson('/api/admin/settings');
        $response->assertStatus(200);

        $profileImageUrl = $response->json('data.profile_image');

        // Should return protected route, not raw storage path
        $this->assertStringContainsString('/profile-image', $profileImageUrl);
        $this->assertStringNotContainsString('/storage/', $profileImageUrl);
    }

}
