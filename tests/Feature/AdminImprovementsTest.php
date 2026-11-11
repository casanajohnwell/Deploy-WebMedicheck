<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Medicine;
use App\Models\MonthlyReport;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\MonthlyReportRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminImprovementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_report_keeps_the_previous_month(): void
    {
        $admin = User::factory()->create();
        $center = $admin->healthCenter();

        MonthlyReport::query()->create([
            'health_center' => $center,
            'year' => 2026,
            'month' => 9,
            'summary' => ['bookings' => 4, 'lines' => []],
        ]);

        app(MonthlyReportRecorder::class)->syncCurrent($center);

        $this->assertDatabaseHas('monthly_reports', [
            'health_center' => $center,
            'year' => 2026,
            'month' => 9,
        ]);
        $this->assertNotNull(MonthlyReport::query()->where('month', now()->month)->where('year', now()->year)->first());

        $this->actingAs($admin)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('AdminMonthlyReport')
                ->where('report.month', now()->month)
                ->where('year', now()->year)
                ->has('report'));
    }

    public function test_approving_a_booking_prefills_a_manual_approval_sms(): void
    {
        $admin = User::factory()->create();
        $medicine = Medicine::factory()->create(['quantity' => 30]);
        $booking = Booking::factory()->create([
            'medicine_id' => $medicine->id,
            'health_center' => $admin->healthCenter(),
            'quantity' => 2,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.bookings.update', $booking), ['status' => 'approved'])
            ->assertRedirect();

        $this->assertDatabaseMissing('sms_messages', [
            'booking_id' => $booking->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.sms', ['booking' => $booking->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('AdminSms')
                ->where('prefill.bookingId', $booking->id)
                ->where('prefill.type', 'approval')
                ->where('prefill.alreadySent', false));
    }

    public function test_claimed_and_unclaimed_bookings_prefill_a_manual_sms(): void
    {
        $admin = User::factory()->create();
        $medicine = Medicine::factory()->create(['name' => 'ORS Sachet', 'quantity' => 30]);

        foreach (['claimed' => 'was claimed at', 'unclaimed' => 'was unclaimed at'] as $status => $phrase) {
            $booking = Booking::factory()->create([
                'medicine_id' => $medicine->id,
                'medicine_name' => $medicine->name,
                'health_center' => $admin->healthCenter(),
                'quantity' => 2,
            ]);
            $booking->approve(3);

            $this->actingAs($admin)
                ->put(route('admin.bookings.update', $booking), ['status' => $status])
                ->assertRedirect();

            $this->actingAs($admin)
                ->get(route('admin.sms', ['booking' => $booking->id]))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('AdminSms')
                    ->where('prefill.status', $status)
                    ->where('prefill.type', $status)
                    ->where('prefill.message', fn (string $message) => str_contains($message, "ORS Sachet {$phrase}"))
                    ->where('prefill.alreadySent', false));
        }
    }

    public function test_admin_can_send_a_manual_sms(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.sms.store'), [
                'recipient' => 'Maria Santos',
                'to' => '09171234567',
                'type' => 'claimed',
                'message' => 'MediCheck: Please claim your medicine.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('sms_messages', [
            'health_center' => $admin->healthCenter(),
            'mode' => SmsMessage::MODE_MANUAL,
            'phone' => '09171234567',
            'status' => 'saved',
        ]);
    }

    public function test_manual_sms_is_texted_through_textbee_when_configured(): void
    {
        config(['services.textbee.key' => 'test-key', 'services.textbee.device_id' => 'device-1']);
        Http::fake(['api.textbee.dev/*' => Http::response(['data' => ['success' => true]])]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.sms.store'), [
                'recipient' => 'Maria Santos',
                'to' => '0917 123 4567',
                'type' => 'approval',
                'message' => 'MediCheck: Your request was approved.',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Http::assertSent(fn ($request) => $request->url() === 'https://api.textbee.dev/api/v1/gateway/send-sms'
            && $request->hasHeader('x-api-key', 'test-key')
            && $request['recipients'] === ['+639171234567']
            && $request['deviceId'] === 'device-1');

        $this->assertDatabaseHas('sms_messages', ['phone' => '0917 123 4567', 'status' => 'sent']);
    }

    public function test_manual_sms_uses_the_health_center_phone(): void
    {
        config(['services.textbee.key' => 'test-key', 'services.textbee.device_id' => 'device-1']);
        Http::fake(['api.textbee.dev/*' => Http::response(['data' => ['success' => true]])]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.sms.device'), ['device_id' => '6AC740222597187C9C5F3698'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('6ac740222597187c9c5f3698', $admin->fresh()->textbee_device_id);

        $this->actingAs($admin->fresh())
            ->post(route('admin.sms.store'), [
                'recipient' => 'Maria Santos',
                'to' => '09171234567',
                'type' => 'approval',
                'message' => 'MediCheck: Your request was approved.',
            ])
            ->assertSessionHasNoErrors();

        Http::assertSent(fn ($request) => $request['deviceId'] === '6ac740222597187c9c5f3698');
    }

    public function test_admin_can_remove_an_sms_from_the_log(): void
    {
        $admin = User::factory()->create();
        $base = [
            'recipient' => 'Maria Santos',
            'phone' => '09171234567',
            'mode' => SmsMessage::MODE_MANUAL,
            'type' => 'approval',
            'message' => 'MediCheck: Your request was approved.',
            'status' => 'sent',
        ];
        $own = SmsMessage::create($base + ['health_center' => $admin->healthCenter()]);
        $other = SmsMessage::create($base + ['health_center' => 'Brgy. Elsewhere Health Center']);

        $this->actingAs($admin)
            ->delete(route('admin.sms.destroy', $other))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.sms.destroy', $own))
            ->assertRedirect();

        $this->assertNotNull($own->fresh()->hidden_from_log_at);
        $this->assertNull($other->fresh()->hidden_from_log_at);

        $this->actingAs($admin)
            ->get(route('admin.sms'))
            ->assertInertia(fn ($page) => $page->has('messages', 0));
    }

    public function test_super_admin_can_see_user_accounts(): void
    {
        $superAdmin = User::factory()->create(['role' => User::ROLE_SUPER_ADMIN]);
        $seeker = User::factory()->seeker()->create(['name' => 'Ana Reyes']);
        Booking::factory()->count(2)->create(['user_id' => $seeker->id]);

        $this->actingAs($superAdmin)
            ->get(route('superadmin.users'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('SuperAdminUsers')
                ->has('seekers', 1)
                ->where('seekers.0.name', 'Ana Reyes')
                ->where('seekers.0.phone', '09171234567')
                ->where('seekers.0.bookings', 2));
    }

    public function test_invalid_device_id_is_rejected(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.sms.device'), ['device_id' => 'my-phone'])
            ->assertSessionHasErrors('device_id');

        $this->assertNull($admin->fresh()->textbee_device_id);
    }

    public function test_failed_textbee_send_is_not_saved(): void
    {
        config(['services.textbee.key' => 'bad-key']);
        Http::fake(['api.textbee.dev/*' => Http::response(['error' => 'Unauthorized'], 401)]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.sms.store'), [
                'recipient' => 'Maria Santos',
                'to' => '09171234567',
                'type' => 'approval',
                'message' => 'MediCheck: Your request was approved.',
            ])
            ->assertSessionHasErrors('to');

        $this->assertDatabaseCount('sms_messages', 0);
    }

    public function test_seeker_cannot_request_an_out_of_stock_medicine(): void
    {
        $seeker = User::factory()->seeker()->create();
        $medicine = Medicine::factory()->create([
            'health_center' => 'Brgy. Magupange Health Center',
            'quantity' => 0,
            'status' => 'out_of_stock',
        ]);

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->postJson('/api/bookings', [
                'medicine_id' => $medicine->id,
                'quantity' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medicine_id']);
    }
}
