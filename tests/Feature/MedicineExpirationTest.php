<?php

namespace Tests\Feature;

use App\Models\Medicine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicineExpirationTest extends TestCase
{
    use RefreshDatabase;

    public function test_past_expiration_is_shown_as_expired(): void
    {
        $medicine = Medicine::factory()->create([
            'quantity' => 50,
            'status' => 'available',
            'expiration' => today()->subDay(),
        ]);

        $data = $medicine->toFrontend();

        $this->assertSame('expired', $data['status']);
        $this->assertSame('available', $data['stockStatus']);
        $this->assertSame(-1, $data['expiresInDays']);
    }

    public function test_upcoming_expiration_reports_days_left(): void
    {
        $medicine = Medicine::factory()->create([
            'quantity' => 50,
            'status' => 'available',
            'expiration' => today()->addDays(10),
        ]);

        $data = $medicine->toFrontend();

        $this->assertSame('available', $data['status']);
        $this->assertSame(10, $data['expiresInDays']);
    }

    public function test_medicine_expiring_today_is_not_expired_yet(): void
    {
        $medicine = Medicine::factory()->create(['expiration' => today()]);

        $this->assertFalse($medicine->isExpired());
        $this->assertSame(0, $medicine->toFrontend()['expiresInDays']);
    }
}
