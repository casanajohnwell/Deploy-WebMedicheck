<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminAccountApproved;
use App\Notifications\AdminAccountDeclined;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class AdminAccountDecisionEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_admin_is_emailed(): void
    {
        Notification::fake();
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $admin = User::factory()->create(['is_approved' => false]);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.accounts.approve', $admin))
            ->assertRedirect()
            ->assertSessionHas('status', 'Barangay administrator approved. An email was sent to let them know.');

        $this->assertTrue($admin->fresh()->is_approved);
        Notification::assertSentTo($admin, AdminAccountApproved::class);
    }

    public function test_declined_admin_is_emailed_with_the_reason(): void
    {
        Notification::fake();
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $admin = User::factory()->create(['is_approved' => false]);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.accounts.decline', $admin), ['reason' => 'Wrong health center.'])
            ->assertRedirect();

        $this->assertModelMissing($admin);
        Notification::assertSentTo(
            $admin,
            AdminAccountDeclined::class,
            fn (AdminAccountDeclined $notification) => $notification->reason === 'Wrong health center.',
        );
    }

    public function test_approval_still_works_when_the_email_fails(): void
    {
        $this->mock(Dispatcher::class, fn ($mock) => $mock->shouldReceive('send')->andThrow(new RuntimeException('Mail server down.')));
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $admin = User::factory()->create(['is_approved' => false]);

        $this->actingAs($superAdmin)
            ->post(route('superadmin.accounts.approve', $admin))
            ->assertSessionHas('status', 'Barangay administrator approved, but the email could not be sent.');

        $this->assertTrue($admin->fresh()->is_approved);
    }
}
