<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Notifications\PasswordResetCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    private function requestCode(User $user): string
    {
        $this->postJson('/api/forgot-password', ['email' => $user->email])->assertOk();

        $code = '';
        Notification::assertSentTo($user, PasswordResetCode::class, function (PasswordResetCode $notification) use (&$code) {
            $code = $notification->code;

            return true;
        });

        return $code;
    }

    public function test_seeker_can_reset_password_with_an_emailed_code(): void
    {
        Notification::fake();

        $seeker = User::factory()->seeker()->create();
        $seeker->createToken('mobile');
        $code = $this->requestCode($seeker);

        $this->postJson('/api/forgot-password/verify', ['email' => $seeker->email, 'code' => $code])
            ->assertOk();

        $this->postJson('/api/forgot-password/reset', [
            'email' => $seeker->email,
            'code' => $code,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-password-123', $seeker->fresh()->password));
        $this->assertSame(0, $seeker->tokens()->count());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $seeker->email]);
    }

    public function test_wrong_code_is_rejected(): void
    {
        Notification::fake();

        $seeker = User::factory()->seeker()->create();
        $code = $this->requestCode($seeker);
        $wrong = $code === '111111' ? '222222' : '111111';

        $this->postJson('/api/forgot-password/verify', ['email' => $seeker->email, 'code' => $wrong])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['code' => 'The code is incorrect.']);

        $this->postJson('/api/forgot-password/reset', [
            'email' => $seeker->email,
            'code' => $wrong,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertUnprocessable();

        $this->assertFalse(Hash::check('new-password-123', $seeker->fresh()->password));
    }

    public function test_admin_email_does_not_receive_a_code_from_the_mobile_app(): void
    {
        Notification::fake();

        $admin = User::factory()->create();

        $this->postJson('/api/forgot-password', ['email' => $admin->email])->assertOk();

        Notification::assertNothingSent();
    }
}
