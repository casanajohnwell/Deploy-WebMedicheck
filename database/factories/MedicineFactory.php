<?php

namespace Database\Factories;

use App\Models\Medicine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Paracetamol 500mg',
            'category' => 'Pain & Fever',
            'health_center' => 'Brgy. Magupange Health Center',
            'quantity' => 100,
            'unit' => 'tablets',
            'expiration' => now()->addYear()->toDateString(),
            'status' => 'available',
        ];
    }
}
