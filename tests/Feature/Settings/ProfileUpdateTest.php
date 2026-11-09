<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_nurse_profile_details_are_view_only()
    {
        $user = User::factory()->create();
        $originalEmail = $user->email;

        $this
            ->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ])
            ->assertForbidden();

        $user->refresh();

        $this->assertNotSame('Test User', $user->name);
        $this->assertSame($originalEmail, $user->email);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_nurse_cannot_delete_their_own_account()
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ])
            ->assertForbidden();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect(route('profile.edit'));

        $this->assertNotNull($user->fresh());
    }
}
