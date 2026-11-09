<?php

namespace Tests\Feature;

use App\Models\MonthlyReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportExportTest extends TestCase
{
    use RefreshDatabase;

    private function adminWithSeptemberReport(): User
    {
        $admin = User::factory()->create(['name' => 'Nurse Joy']);

        MonthlyReport::query()->create([
            'health_center' => $admin->healthCenter(),
            'year' => 2026,
            'month' => 9,
            'summary' => [
                'bookings' => 1,
                'approved' => 1,
                'pending' => 0,
                'declined' => 0,
                'low_stock' => 2,
                'out_of_stock' => 0,
                'most_taken' => ['medicine' => 'Paracetamol', 'quantity' => 5, 'times' => 1],
                'lines' => [
                    ['id' => 1, 'date' => '2026-09-03 09:15', 'seeker' => 'Ana Reyes', 'medicine' => 'Paracetamol', 'quantity' => 5, 'status' => 'approved'],
                ],
            ],
        ]);

        return $admin;
    }

    public function test_admin_can_open_the_printable_report(): void
    {
        $admin = $this->adminWithSeptemberReport();

        $this->actingAs($admin)
            ->get(route('admin.reports.print', ['year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertSee('MediCheck Monthly Report')
            ->assertSee('September 2026')
            ->assertSee($admin->healthCenter())
            ->assertSee('Ana Reyes')
            ->assertSee('Nurse Joy');
    }

    public function test_admin_can_download_the_report_as_csv(): void
    {
        $admin = $this->adminWithSeptemberReport();

        $response = $this->actingAs($admin)
            ->get(route('admin.reports.export', ['year' => 2026, 'month' => 9]))
            ->assertOk()
            ->assertDownload('medicheck-report-2026-09.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('"Ana Reyes",Paracetamol,5,Approved', $csv);
        $this->assertStringContainsString('"Low stock",2', $csv);
    }

    public function test_seeker_cannot_export_reports(): void
    {
        $seeker = User::factory()->seeker()->create();

        $this->actingAs($seeker)
            ->get(route('admin.reports.export', ['year' => 2026, 'month' => 9]))
            ->assertForbidden();
    }
}
