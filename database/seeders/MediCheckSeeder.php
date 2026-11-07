<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Medicine;
use App\Models\User;
use App\Services\MonthlyReportRecorder;
use App\Services\SmsNotifier;
use Illuminate\Database\Seeder;

class MediCheckSeeder extends Seeder
{
    public function run(): void
    {
        if (User::query()->exists()) {
            return;
        }

        $center = 'Brgy. Magupange Health Center';

        $admin = User::query()->create([
            'name' => 'Barangay Nurse',
            'email' => 'nurse@gmail.com',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
            'is_approved' => true,
            'phone' => '09170000001',
            'position' => 'NURSE',
            'region' => 'Mindanao',
            'province' => 'Surigao Del Norte',
            'city' => 'Placer',
            'barangay' => 'Magupange',
            'street' => $center,
            'postal_code' => '8405',
        ]);

        $seeker = User::query()->create([
            'name' => 'Maria Santos',
            'email' => 'maria@gmail.com',
            'password' => 'password',
            'role' => User::ROLE_SEEKER,
            'phone' => '09171234567',
            'region' => 'Mindanao',
            'province' => 'Surigao Del Norte',
            'city' => 'Placer',
            'barangay' => 'Magupange',
            'street' => 'Poblacion',
            'postal_code' => '8405',
        ]);

        $paracetamol = Medicine::query()->create([
            'name' => 'Paracetamol 500mg',
            'category' => 'Pain & Fever',
            'health_center' => $center,
            'quantity' => 86,
            'unit' => 'tablets',
            'expiration' => '2027-06-12',
            'status' => 'available',
        ]);

        $amoxicillin = Medicine::query()->create([
            'name' => 'Amoxicillin 500mg',
            'category' => 'Antibiotic',
            'health_center' => $center,
            'quantity' => 14,
            'unit' => 'capsules',
            'expiration' => '2026-12-08',
            'status' => 'low',
        ]);

        Medicine::query()->create([
            'name' => 'ORS Sachet',
            'category' => 'First Aid',
            'health_center' => $center,
            'quantity' => 40,
            'unit' => 'sachets',
            'expiration' => '2028-02-01',
            'status' => 'available',
        ]);

        Medicine::query()->create([
            'name' => 'Metformin 500mg',
            'category' => 'Maintenance',
            'health_center' => $center,
            'quantity' => 0,
            'unit' => 'tablets',
            'expiration' => '2027-05-20',
            'status' => 'out_of_stock',
        ]);

        $pending = Booking::query()->create([
            'user_id' => $seeker->id,
            'medicine_id' => $paracetamol->id,
            'seeker_name' => $seeker->name,
            'medicine_name' => $paracetamol->name,
            'quantity' => 10,
            'health_center' => $center,
            'phone' => '09171234567',
            'notes' => 'For fever.',
            'status' => Booking::STATUS_PENDING,
        ]);

        Booking::query()->create([
            'user_id' => $seeker->id,
            'medicine_id' => $amoxicillin->id,
            'seeker_name' => $seeker->name,
            'medicine_name' => $amoxicillin->name,
            'quantity' => 6,
            'health_center' => $center,
            'phone' => '09171234567',
            'notes' => 'For infection.',
            'status' => Booking::STATUS_APPROVED,
        ]);

        app(SmsNotifier::class)->bookingUpdate($pending);
        app(MonthlyReportRecorder::class)->syncCurrent($center);

        $admin->touch();
    }
}
