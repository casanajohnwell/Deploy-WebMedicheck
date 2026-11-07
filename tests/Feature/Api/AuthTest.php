<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function seekerPayload(array $overrides = []): array
    {
        return [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '09171234567',
            'region' => 'Mindanao',
            'province' => 'Surigao Del Norte',
            'city' => 'Placer',
            'barangay' => 'Magupange',
            'street' => 'Poblacion Street',
            'postal_code' => '8405',
            'device_name' => 'android-test',
            ...$overrides,
        ];
    }

    public function test_mobile_seeker_can_register_and_receive_a_token(): void
    {
        User::factory()->create();

        $response = $this->postJson('/api/register', $this->seekerPayload());

        $response->assertCreated()
            ->assertJsonPath('user.role', User::ROLE_SEEKER)
            ->assertJsonPath('user.email', 'maria@example.com')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role', 'phone', 'barangay']]);

        $this->assertDatabaseHas('users', [
            'email' => 'maria@example.com',
            'role' => User::ROLE_SEEKER,
        ]);
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_mobile_register_rejects_invalid_payload(): void
    {
        $this->postJson('/api/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password', 'phone', 'region', 'province', 'city', 'barangay']);
    }

    public function test_mobile_user_can_login_and_fetch_profile(): void
    {
        $user = User::factory()->seeker()->create([
            'email' => 'seeker@example.com',
        ]);

        $login = $this->postJson('/api/login', [
            'email' => 'seeker@example.com',
            'password' => 'password',
            'device_name' => 'iphone',
        ]);

        $login->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.role', User::ROLE_SEEKER);

        $token = $login->json('token');

        $this->getJson('/api/user', [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('user.email', 'seeker@example.com')
            ->assertJsonPath('user.barangay', 'Magupange');
    }

    public function test_mobile_login_rejects_bad_credentials(): void
    {
        User::factory()->seeker()->create([
            'email' => 'seeker@example.com',
        ]);

        $this->postJson('/api/login', [
            'email' => 'seeker@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_mobile_user_can_logout_and_token_stops_working(): void
    {
        $user = User::factory()->seeker()->create();
        $token = $user->createToken('android')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_mobile_user_can_update_profile_and_password(): void
    {
        $user = User::factory()->seeker()->create([
            'email' => 'seeker@example.com',
        ]);
        $token = $user->createToken('android')->plainTextToken;

        $this->withToken($token)
            ->patchJson('/api/me', [
                'name' => 'Maria Cruz',
                'phone' => '09180001111',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Maria Cruz')
            ->assertJsonPath('user.phone', '09180001111');

        $this->withToken($token)
            ->putJson('/api/me/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk();

        $this->postJson('/api/login', [
            'email' => 'seeker@example.com',
            'password' => 'new-password',
        ])->assertOk();
    }

    public function test_mobile_user_can_upload_a_profile_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->seeker()->create();
        $token = $user->createToken('android')->plainTextToken;
        $file = UploadedFile::fake()->image('me.jpg', 240, 240);

        $this->withToken($token)
            ->post('/api/me/avatar', [
                'avatar' => $file,
            ])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        $this->assertNotEmpty($user->avatarUrl());
    }

    public function test_admin_can_also_login_through_the_api(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.com',
        ]);

        $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.id', $admin->id)
            ->assertJsonPath('user.role', User::ROLE_ADMIN);
    }
}
