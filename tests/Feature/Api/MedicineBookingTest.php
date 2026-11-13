<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicineBookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeker_can_list_medicines_and_create_a_booking(): void
    {
        $user = User::factory()->seeker()->create();
        $medicine = Medicine::factory()->create([
            'name' => 'ORS Sachet',
            'quantity' => 40,
        ]);
        Medicine::factory()->create([
            'name' => 'Expired Drug',
            'expiration' => now()->subDay()->toDateString(),
            'quantity' => 10,
        ]);
        Medicine::factory()->create([
            'name' => 'Empty Shelf',
            'quantity' => 0,
            'status' => 'out_of_stock',
        ]);

        $token = $user->createToken('android')->plainTextToken;
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/medicines', $headers)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['name' => 'ORS Sachet', 'status' => 'available'])
            ->assertJsonFragment(['name' => 'Empty Shelf', 'status' => 'out_of_stock']);

        $this->postJson('/api/bookings', [
            'medicine_id' => $medicine->id,
            'quantity' => 4,
            'notes' => 'For dehydration.',
        ], $headers)
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.medicine', 'ORS Sachet')
            ->assertJsonPath('data.quantity', 4);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'medicine_id' => $medicine->id,
            'status' => Booking::STATUS_PENDING,
            'stock_reserved' => false,
        ]);
        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'quantity' => 40,
        ]);
    }

    public function test_seeker_cannot_request_more_than_the_maximum_quantity(): void
    {
        $user = User::factory()->seeker()->create();
        $medicine = Medicine::factory()->create(['quantity' => 100]);

        $this->withToken($user->createToken('android')->plainTextToken)
            ->postJson('/api/bookings', [
                'medicine_id' => $medicine->id,
                'quantity' => Booking::MAX_QUANTITY + 1,
                'phone' => '09171234567',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_seeker_can_request_the_same_medicine_only_once_a_day(): void
    {
        $user = User::factory()->seeker()->create();
        $medicine = Medicine::factory()->create(['quantity' => 100]);
        $other = Medicine::factory()->create(['quantity' => 100]);
        $this->withToken($user->createToken('android')->plainTextToken);

        $this->postJson('/api/bookings', ['medicine_id' => $medicine->id, 'quantity' => 20, 'phone' => '09171234567'])
            ->assertCreated();

        $this->postJson('/api/bookings', ['medicine_id' => $medicine->id, 'quantity' => 1, 'phone' => '09171234567'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('medicine_id');

        $this->postJson('/api/bookings', ['medicine_id' => $other->id, 'quantity' => 5, 'phone' => '09171234567'])
            ->assertCreated();

        $this->travel(1)->days();

        $this->postJson('/api/bookings', ['medicine_id' => $medicine->id, 'quantity' => 3, 'phone' => '09171234567'])
            ->assertCreated();
    }

    public function test_seeker_can_cancel_a_pending_booking(): void
    {
        $user = User::factory()->seeker()->create();
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
        ]);
        $token = $user->createToken('android')->plainTextToken;

        $this->postJson('/api/bookings/'.$booking->id.'/cancel', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk()
            ->assertJsonPath('data.status', Booking::STATUS_UNCLAIMED);
    }

    public function test_admin_can_approve_a_mobile_booking_from_the_web(): void
    {
        $admin = User::factory()->create();
        $medicine = Medicine::factory()->create(['quantity' => 50]);
        $booking = Booking::factory()->create([
            'medicine_id' => $medicine->id,
            'medicine_name' => $medicine->name,
            'quantity' => 10,
            'health_center' => $medicine->health_center,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.bookings.update', $booking), [
                'status' => 'approved',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_APPROVED,
        ]);
        $this->assertDatabaseHas('medicines', [
            'id' => $medicine->id,
            'quantity' => 50,
        ]);
    }

    public function test_stock_is_deducted_only_when_the_medicine_is_claimed(): void
    {
        $admin = User::factory()->create();
        $medicine = Medicine::factory()->create(['quantity' => 50]);
        $booking = Booking::factory()->create([
            'medicine_id' => $medicine->id,
            'medicine_name' => $medicine->name,
            'quantity' => 10,
            'health_center' => $medicine->health_center,
        ]);

        $this->actingAs($admin)->put(route('admin.bookings.update', $booking), ['status' => 'approved']);
        $this->assertSame(50, $medicine->fresh()->quantity);

        $this->actingAs($admin)
            ->put(route('admin.bookings.update', $booking), ['status' => 'claimed'])
            ->assertRedirect();

        $this->assertSame(40, $medicine->fresh()->quantity);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => Booking::STATUS_CLAIMED,
            'stock_reserved' => true,
        ]);
    }

    public function test_approvals_cannot_promise_more_than_the_stock_on_hand(): void
    {
        $admin = User::factory()->create();
        $medicine = Medicine::factory()->create(['quantity' => 30]);
        [$ana, $ben] = Booking::factory()->count(2)->create([
            'medicine_id' => $medicine->id,
            'medicine_name' => $medicine->name,
            'quantity' => 20,
            'health_center' => $medicine->health_center,
            'status' => Booking::STATUS_PENDING,
            'stock_reserved' => false,
        ]);

        $this->actingAs($admin)->put(route('admin.bookings.update', $ana), ['status' => 'approved'])
            ->assertSessionHasNoErrors();

        $this->actingAs($admin)->put(route('admin.bookings.update', $ben), ['status' => 'approved'])
            ->assertSessionHasErrors('status');

        $this->assertSame(Booking::STATUS_PENDING, $ben->fresh()->status);
        $this->assertSame(30, $medicine->fresh()->quantity);
        $this->assertSame(10, $medicine->fresh()->availableQuantity());

        $this->actingAs($admin)->put(route('admin.bookings.update', $ana), ['status' => 'unclaimed']);

        $this->actingAs($admin)->put(route('admin.bookings.update', $ben), ['status' => 'approved'])
            ->assertSessionHasNoErrors();
        $this->assertSame(Booking::STATUS_APPROVED, $ben->fresh()->status);
    }

    public function test_mobile_sees_and_requests_against_available_stock_only(): void
    {
        $seeker = User::factory()->seeker()->create();
        $medicine = Medicine::factory()->create(['name' => 'Amoxicillin', 'quantity' => 30]);
        Booking::factory()->create([
            'medicine_id' => $medicine->id,
            'medicine_name' => $medicine->name,
            'quantity' => 20,
            'health_center' => $medicine->health_center,
            'status' => Booking::STATUS_APPROVED,
            'stock_reserved' => false,
        ]);
        $this->withToken($seeker->createToken('android')->plainTextToken);

        $this->getJson('/api/medicines')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Amoxicillin', 'quantity' => 10, 'status' => 'low']);

        $this->postJson('/api/bookings', ['medicine_id' => $medicine->id, 'quantity' => 15, 'phone' => '09171234567'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->postJson('/api/bookings', ['medicine_id' => $medicine->id, 'quantity' => 10, 'phone' => '09171234567'])
            ->assertCreated();
    }

    public function test_seeker_can_request_again_the_same_day_after_a_decline(): void
    {
        $seeker = User::factory()->seeker()->create();
        $medicine = Medicine::factory()->create(['quantity' => 100]);
        $this->withToken($seeker->createToken('android')->plainTextToken);

        $id = $this->postJson('/api/bookings', ['medicine_id' => $medicine->id, 'quantity' => 20, 'phone' => '09171234567'])
            ->assertCreated()
            ->json('data.id');

        Booking::query()->findOrFail($id)->update(['status' => Booking::STATUS_DECLINED]);

        $this->postJson('/api/bookings', ['medicine_id' => $medicine->id, 'quantity' => 10, 'phone' => '09171234567'])
            ->assertCreated();
    }

    public function test_guest_cannot_access_mobile_endpoints(): void
    {
        $this->getJson('/api/medicines')->assertUnauthorized();
        $this->getJson('/api/bookings')->assertUnauthorized();
        $this->postJson('/api/bookings', ['medicine_id' => 1, 'quantity' => 1])->assertUnauthorized();
    }
}
