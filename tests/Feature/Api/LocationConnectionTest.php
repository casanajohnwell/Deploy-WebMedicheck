<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocationConnectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_locations_lists_barangays_opened_by_admins(): void
    {
        User::factory()->create();
        User::factory()->create([
            'barangay' => 'San Isidro',
            'city' => 'Placer',
        ]);
        User::factory()->seeker()->create();

        $this->getJson('/api/locations')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'barangay' => 'Magupange',
                'city' => 'Placer',
                'health_center' => 'Brgy. Magupange Health Center',
            ])
            ->assertJsonFragment([
                'barangay' => 'San Isidro',
                'health_center' => 'Brgy. San Isidro Health Center',
            ]);
    }

    public function test_mobile_register_requires_an_admin_lugar(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '09171234567',
            'region' => 'Mindanao',
            'province' => 'Surigao Del Norte',
            'city' => 'Placer',
            'barangay' => 'Magupange',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['barangay']);
    }

    public function test_seeker_only_sees_medicines_from_the_admin_lugar(): void
    {
        User::factory()->create();
        $seeker = User::factory()->seeker()->create();
        Medicine::factory()->create([
            'name' => 'Paracetamol 500mg',
            'health_center' => 'Brgy. Magupange Health Center',
        ]);
        Medicine::factory()->create([
            'name' => 'Amoxicillin 500mg',
            'health_center' => 'Brgy. San Isidro Health Center',
        ]);

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->getJson('/api/medicines')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Paracetamol 500mg');
    }

    public function test_seeker_cannot_book_medicine_from_another_lugar(): void
    {
        $seeker = User::factory()->seeker()->create();
        $medicine = Medicine::factory()->create([
            'health_center' => 'Brgy. San Isidro Health Center',
        ]);

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->postJson('/api/bookings', [
                'medicine_id' => $medicine->id,
                'quantity' => 2,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['medicine_id']);
    }

    public function test_admin_web_only_shares_data_for_their_lugar(): void
    {
        $admin = User::factory()->create();
        $local = Medicine::factory()->create([
            'name' => 'Local Stock',
            'health_center' => 'Brgy. Magupange Health Center',
        ]);
        $other = Medicine::factory()->create([
            'name' => 'Other Stock',
            'health_center' => 'Brgy. San Isidro Health Center',
        ]);
        Booking::factory()->create([
            'medicine_id' => $local->id,
            'health_center' => 'Brgy. Magupange Health Center',
        ]);
        Booking::factory()->create([
            'medicine_id' => $other->id,
            'health_center' => 'Brgy. San Isidro Health Center',
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('station.barangay', 'Magupange')
                ->has('medicines', 1)
                ->where('medicines.0.name', 'Local Stock')
                ->has('bookings', 1)
                ->where('bookings.0.healthCenter', 'Brgy. Magupange Health Center'));
    }

    public function test_admin_can_set_lugar_and_connect_mobile_users(): void
    {
        $admin = User::factory()->create([
            'barangay' => null,
            'city' => null,
            'province' => null,
            'region' => null,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.location.update'), [
                'region' => 'Mindanao',
                'province' => 'Surigao Del Norte',
                'city' => 'Placer',
                'barangay' => 'Magupange',
                'postal_code' => '8405',
            ])
            ->assertRedirect('/admin/dashboard');

        $admin->refresh();

        $this->assertSame('Magupange', $admin->barangay);
        $this->assertSame('Brgy. Magupange Health Center', $admin->healthCenter());

        $this->postJson('/api/register', [
            'name' => 'Maria Santos',
            'email' => 'maria@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '09171234567',
            'region' => 'Mindanao',
            'province' => 'Surigao Del Norte',
            'city' => 'Placer',
            'barangay' => 'Magupange',
        ])->assertCreated()
            ->assertJsonPath('user.health_center', 'Brgy. Magupange Health Center');
    }

    public function test_any_admin_lugar_connects_even_if_spelling_differs(): void
    {
        User::factory()->create([
            'barangay' => 'San Isidro',
            'city' => 'Placer',
            'province' => 'Surigao Del Norte',
            'region' => 'Mindanao',
        ]);
        $seeker = User::factory()->seeker()->create([
            'barangay' => 'San Isidro',
            'city' => 'Placer',
        ]);
        Medicine::factory()->create([
            'name' => 'Amoxicillin 500mg',
            'health_center' => 'Brgy. San Isidro Health Center',
        ]);
        Medicine::factory()->create([
            'name' => 'Paracetamol 500mg',
            'health_center' => 'Brgy. Magupange Health Center',
        ]);

        $this->postJson('/api/register', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '09180001111',
            'region' => 'Mindanao',
            'province' => 'surigao del norte',
            'city' => 'Municipality of Placer',
            'barangay' => 'san isidro',
        ])->assertCreated()
            ->assertJsonPath('user.barangay', 'San Isidro')
            ->assertJsonPath('user.health_center', 'Brgy. San Isidro Health Center');

        $this->withToken($seeker->createToken('android')->plainTextToken)
            ->getJson('/api/medicines')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Amoxicillin 500mg');
    }
}
