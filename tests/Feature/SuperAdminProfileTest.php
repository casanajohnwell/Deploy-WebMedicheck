<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\EmailChangeCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SuperAdminProfileTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
    }

    public function test_profile_page_renders_for_super_admin()
    {
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->get(route('superadmin.profile'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('SuperAdminProfile')->where('email', $super->email)->where('pendingEmail', null));
    }

    public function test_super_admin_can_change_email_after_confirming_code()
    {
        Notification::fake();

        $super = $this->superAdmin();

        $this->actingAs($super)
            ->post(route('superadmin.profile.email'), [
                'email' => 'real.owner@gmail.com',
                'current_password' => 'password',
            ])
            ->assertSessionHasNoErrors();

        $code = null;
        Notification::assertSentTo(
            new AnonymousNotifiable,
            EmailChangeCode::class,
            function (EmailChangeCode $notification, array $channels, AnonymousNotifiable $notifiable) use (&$code) {
                $code = $notification->code;

                return $notifiable->routes['mail'] === 'real.owner@gmail.com';
            },
        );

        $this->actingAs($super)
            ->get(route('superadmin.profile'))
            ->assertInertia(fn ($page) => $page->where('pendingEmail', 'real.owner@gmail.com'));

        $this->actingAs($super)
            ->post(route('superadmin.profile.email.verify'), ['code' => $code])
            ->assertSessionHasNoErrors();

        $this->assertSame('real.owner@gmail.com', $super->fresh()->email);
    }

    public function test_wrong_code_does_not_change_email()
    {
        Notification::fake();

        $super = $this->superAdmin();
        $original = $super->email;

        $this->actingAs($super)->post(route('superadmin.profile.email'), [
            'email' => 'real.owner@gmail.com',
            'current_password' => 'password',
        ]);

        $this->actingAs($super)
            ->post(route('superadmin.profile.email.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertSame($original, $super->fresh()->email);
    }

    public function test_email_change_requires_current_password()
    {
        Notification::fake();

        $this->actingAs($this->superAdmin())
            ->post(route('superadmin.profile.email'), [
                'email' => 'real.owner@gmail.com',
                'current_password' => 'wrong-password',
            ])
            ->assertSessionHasErrors('current_password');

        Notification::assertNothingSent();
    }

    public function test_super_admin_can_change_password()
    {
        $super = $this->superAdmin();

        $this->actingAs($super)
            ->put(route('superadmin.profile.password'), [
                'current_password' => 'password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-password-123', $super->fresh()->password));
    }

    public function test_nurse_cannot_open_super_admin_profile()
    {
        $this->actingAs(User::factory()->create())
            ->get(route('superadmin.profile'))
            ->assertRedirect();
    }
}
