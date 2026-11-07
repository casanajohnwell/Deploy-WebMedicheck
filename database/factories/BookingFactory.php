<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->seeker(),
            'medicine_id' => Medicine::factory(),
            'seeker_name' => fake()->name(),
            'medicine_name' => 'Paracetamol 500mg',
            'quantity' => 10,
            'health_center' => 'Brgy. Magupange Health Center',
            'phone' => '09171234567',
            'notes' => 'For fever.',
            'status' => Booking::STATUS_PENDING,
        ];
    }
}
