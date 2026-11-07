<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        if (Medicine::query()->exists()) {
            return;
        }

        $magupange = 'Brgy. Magupange Health Center';

        Medicine::query()->insert([
            [
                'name' => 'Paracetamol 500mg',
                'category' => 'Pain & Fever',
                'health_center' => 'Brgy. San Isidro Health Center',
                'quantity' => 240,
                'unit' => 'tablets',
                'expiration' => '2027-03-12',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Amoxicillin 500mg',
                'category' => 'Antibiotic',
                'health_center' => 'Brgy. Mabini Health Center',
                'quantity' => 18,
                'unit' => 'capsules',
                'expiration' => '2026-11-02',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Losartan 50mg',
                'category' => 'Maintenance',
                'health_center' => 'Brgy. Poblacion Health Center',
                'quantity' => 96,
                'unit' => 'tablets',
                'expiration' => '2027-08-20',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Salbutamol 2mg',
                'category' => 'Cough & Colds',
                'health_center' => 'Brgy. San Isidro Health Center',
                'quantity' => 8,
                'unit' => 'tablets',
                'expiration' => '2026-09-18',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Metformin 500mg',
                'category' => 'Maintenance',
                'health_center' => 'Brgy. Santa Cruz Health Center',
                'quantity' => 0,
                'unit' => 'tablets',
                'expiration' => '2026-04-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ORS Sachet',
                'category' => 'First Aid',
                'health_center' => 'Brgy. Poblacion Health Center',
                'quantity' => 150,
                'unit' => 'sachets',
                'expiration' => '2028-01-15',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cetirizine 10mg',
                'category' => 'Cough & Colds',
                'health_center' => 'Brgy. Mabini Health Center',
                'quantity' => 42,
                'unit' => 'tablets',
                'expiration' => '2027-05-09',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ferrous Sulfate',
                'category' => 'Vitamins',
                'health_center' => 'Brgy. Santa Cruz Health Center',
                'quantity' => 12,
                'unit' => 'tablets',
                'expiration' => '2026-10-30',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Paracetamol 500mg',
                'category' => 'Pain & Fever',
                'health_center' => $magupange,
                'quantity' => 86,
                'unit' => 'tablets',
                'expiration' => '2027-06-12',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Amoxicillin 500mg',
                'category' => 'Antibiotic',
                'health_center' => $magupange,
                'quantity' => 14,
                'unit' => 'capsules',
                'expiration' => '2026-12-08',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'ORS Sachet',
                'category' => 'First Aid',
                'health_center' => $magupange,
                'quantity' => 40,
                'unit' => 'sachets',
                'expiration' => '2028-02-01',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Metformin 500mg',
                'category' => 'Maintenance',
                'health_center' => $magupange,
                'quantity' => 0,
                'unit' => 'tablets',
                'expiration' => '2026-05-20',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
