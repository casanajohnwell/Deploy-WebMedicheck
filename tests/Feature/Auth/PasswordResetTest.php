<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipUnlessFortifyHas(Features::resetPasswords());
    }

    public function test_forgot_password_screen_can_be_rendered()
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/ForgotPassword')->where('step', 'email'));
    }

    public function test_reset_code_is_emailed_to_an_admin()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.request'));

        Notification::assertSentTo($user, PasswordResetCode::class);

        $this->get(route('password.request'))
            ->assertInertia(fn ($page) => $page->where('step', 'code')->where('email', $user->email));
    }

    public function test_seeker_email_does_not_receive_a_code()
    {
        Notification::fake();

        $seeker = User::factory()->seeker()->create();

        $this->post(route('password.email'), ['email' => $seeker->email])->assertSessionHasNoErrors();

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_the_emailed_code()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        $code = null;
        Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $this->post(route('password.verify-code'), ['code' => $code])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.request'));

        $this->get(route('password.request'))
            ->assertInertia(fn ($page) => $page->where('step', 'password'));

        $this->post(route('password.code-update'), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_super_admin_can_reset_password_with_a_code()
    {
        Notification::fake();

        $super = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);

        $this->get(route('password.request', ['for' => 'superadmin']))
            ->assertInertia(fn ($page) => $page->where('forSuperAdmin', true)->where('loginUrl', '/superadmin'));

        $this->post(route('password.email'), ['email' => $super->email]);

        $code = null;
        Notification::assertSentTo($super, PasswordResetCode::class, function (PasswordResetCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $this->post(route('password.verify-code'), ['code' => $code])->assertSessionHasNoErrors();

        $this->post(route('password.code-update'), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('superadmin.login'));

        $this->assertTrue(Hash::check('new-password-123', $super->fresh()->password));
    }

    public function test_wrong_code_is_rejected_and_password_cannot_be_changed()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        $code = null;
        Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $wrong = $code === '111111' ? '222222' : '111111';

        $this->post(route('password.verify-code'), ['code' => $wrong])
            ->assertSessionHasErrors('code');

        $this->post(route('password.code-update'), [
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertSessionHasErrors('code');

        $this->assertFalse(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_expired_code_is_rejected()
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        $code = null;
        Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        $this->travel(11)->minutes();

        $this->post(route('password.verify-code'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'This code has expired. Send a new code.']);
    }
}
