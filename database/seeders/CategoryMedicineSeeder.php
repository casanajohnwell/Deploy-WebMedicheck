<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Services\MonthlyReportRecorder;
use Illuminate\Database\Seeder;

class CategoryMedicineSeeder extends Seeder
{
    public const HEALTH_CENTER = 'Brgy. Magupange Health Center';

    /**
     * @var array<string, list<array{0: string, 1: int, 2: string}>>
     */
    private const MEDICINES = [
        'Vitamins' => [
            ['Multivitamins', 300, 'tablets'],
            ['Ascorbic Acid 500mg', 400, 'tablets'],
            ['Ferrous Sulfate + Folic Acid', 300, 'tablets'],
            ['Vitamin B Complex', 250, 'tablets'],
            ['Calcium Carbonate 500mg', 200, 'tablets'],
            ['Zinc Sulfate Syrup 20mg/5mL', 60, 'bottles'],
        ],
        'Antibiotic' => [
            ['Amoxicilin 500mg', 300, 'capsules'],
            ['Cefalexin 500mg', 200, 'capsules'],
            ['Co-trimoxazole 800mg/160mg', 200, 'tablets'],
            ['Cloxacillin 500mg', 150, 'capsules'],
            ['Metronidazole 500mg', 200, 'tablets'],
            ['Azithromycin 500mg', 100, 'tablets'],
        ],
        'Maintenance' => [
            ['Metformin 500mg', 400, 'tablets'],
            ['Amlodipine 5mg', 400, 'tablets'],
            ['Losartan 50mg', 400, 'tablets'],
            ['Gliclazide 80mg', 200, 'tablets'],
            ['Simvastatin 20mg', 250, 'tablets'],
            ['Metoprolol 50mg', 200, 'tablets'],
        ],
        'Pain & Fever' => [
            ['Mefenamic', 700, 'capsules'],
            ['Paracetamol 500mg', 500, 'tablets'],
            ['Ibuprofen 400mg', 300, 'tablets'],
            ['Paracetamol Syrup 250mg/5mL', 80, 'bottles'],
            ['Naproxen Sodium 550mg', 150, 'tablets'],
            ['Paracetamol Drops 100mg/mL', 50, 'bottles'],
        ],
        'Cough & Colds' => [
            ['Carbocisteine 500mg', 300, 'capsules'],
            ['Ambroxol 30mg', 250, 'tablets'],
            ['Lagundi 600mg', 300, 'tablets'],
            ['Cetirizine 10mg', 300, 'tablets'],
            ['Phenylephrine + Chlorphenamine (cold tablet)', 300, 'tablets'],
            ['Salbutamol 2mg', 200, 'tablets'],
        ],
        'First Aid' => [
            ['Oral Rehydration Salts', 300, 'sachets'],
            ['Povidone-Iodine 10% Solution', 50, 'bottles'],
            ['Isopropyl Alcohol 70%', 60, 'bottles'],
            ['Hydrogen Peroxide 3%', 40, 'bottles'],
            ['Silver Sulfadiazine Cream 1%', 30, 'tubes'],
            ['Loperamide 2mg', 200, 'capsules'],
        ],
    ];

    /**
     * @var array<string, string>
     */
    private const CATEGORY_BY_NAME = [
        'amox' => 'Antibiotic',
        'metformin' => 'Maintenance',
        'mefenamic' => 'Pain & Fever',
        'paracetamol' => 'Pain & Fever',
        'biogesic' => 'Pain & Fever',
        'alaxan' => 'Pain & Fever',
        'tuseran' => 'Cough & Colds',
        'diatabs' => 'First Aid',
        'jocel' => 'Vitamins',
        'ascorbic' => 'Vitamins',
        'multivit' => 'Vitamins',
    ];

    public function run(): void
    {
        foreach (Medicine::query()->get() as $medicine) {
            $name = strtolower($medicine->name);

            foreach (self::CATEGORY_BY_NAME as $keyword => $category) {
                if (str_contains($name, $keyword)) {
                    if ($medicine->category !== $category) {
                        $medicine->update(['category' => $category]);
                    }

                    break;
                }
            }
        }

        $expiration = now()->addYears(2)->startOfMonth()->toDateString();

        foreach (self::MEDICINES as $category => $items) {
            foreach ($items as [$name, $quantity, $unit]) {
                Medicine::query()->firstOrCreate(
                    ['health_center' => self::HEALTH_CENTER, 'name' => $name],
                    [
                        'category' => $category,
                        'quantity' => $quantity,
                        'unit' => $unit,
                        'expiration' => $expiration,
                        'status' => 'available',
                    ],
                );
            }
        }

        app(MonthlyReportRecorder::class)->syncCurrent(self::HEALTH_CENTER);
    }
}
