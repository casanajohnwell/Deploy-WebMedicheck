<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Medicine;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsInboxTest extends TestCase
{
    use RefreshDatabase;

    private function bookingFor(User $seeker, string $status = Booking::STATUS_PENDING): Booking
    {
        $medicine = Medicine::factory()->create(['name' => 'ORS Sachet', 'quantity' => 50]);

        return Booking::query()->create([
            'user_id' => $seeker->id,
            'medicine_id' => $medicine->id,
            'seeker_name' => $seeker->name,
            'medicine_name' => $medicine->name,
            'quantity' => 2,
            'health_center' => $medicine->health_center,
            'phone' => '09171234567',
            'status' => $status,
        ]);
    }

    public function test_seeker_sees_approval_in_the_app_before_the_nurse_sends_the_sms(): void
    {
        $seeker = User::factory()->seeker()->create();
        $booking = $this->bookingFor($seeker);
        $booking->approve(3);

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->getJson('/api/sms')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', "booking-{$booking->id}-approval")
            ->assertJsonPath('data.0.type', 'approval')
            ->assertJsonPath('data.0.read', false)
            ->assertJsonPath('data.0.related_booking_id', (string) $booking->id);
    }

    public function test_seeker_sees_decline_in_the_app(): void
    {
        $seeker = User::factory()->seeker()->create();
        $booking = $this->bookingFor($seeker);
        $booking->decline();

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->getJson('/api/sms')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'decline');
    }

    public function test_pending_bookings_do_not_create_a_notice(): void
    {
        $seeker = User::factory()->seeker()->create();
        $this->bookingFor($seeker);

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->getJson('/api/sms')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_sent_sms_replaces_the_app_notice_without_duplicates(): void
    {
        $seeker = User::factory()->seeker()->create();
        $booking = $this->bookingFor($seeker);
        $booking->approve(3);

        $sms = SmsMessage::query()->create([
            'user_id' => $seeker->id,
            'booking_id' => $booking->id,
            'health_center' => $booking->health_center,
            'recipient' => $seeker->name,
            'phone' => $booking->phone,
            'mode' => SmsMessage::MODE_MANUAL,
            'type' => 'approval',
            'message' => 'MediCheck: Your request was approved.',
            'status' => 'sent',
        ]);

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->getJson('/api/sms')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $sms->id);
    }

    public function test_approval_notice_stays_after_the_medicine_is_claimed(): void
    {
        $seeker = User::factory()->seeker()->create();
        $booking = $this->bookingFor($seeker);
        $booking->approve(3);
        $booking->claim();

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->getJson('/api/sms')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'approval');
    }

    public function test_seeker_only_sees_their_own_notices(): void
    {
        $seeker = User::factory()->seeker()->create();
        $other = User::factory()->seeker()->create();
        $this->bookingFor($other)->approve(3);

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->getJson('/api/sms')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
