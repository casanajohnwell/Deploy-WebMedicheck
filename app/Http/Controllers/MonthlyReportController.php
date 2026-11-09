<?php

namespace App\Http\Controllers;

use App\Models\MonthlyReport;
use App\Services\MonthlyReportRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyReportController extends Controller
{
    public function index(Request $request, MonthlyReportRecorder $recorder): Response
    {
        ['year' => $year, 'month' => $month, 'report' => $report] = $this->resolve($request, $recorder);

        return Inertia::render('AdminMonthlyReport', [
            'year' => $year,
            'month' => $month,
            'report' => $report->toFrontend(),
        ]);
    }

    public function export(Request $request, MonthlyReportRecorder $recorder): StreamedResponse
    {
        ['year' => $year, 'month' => $month, 'healthCenter' => $healthCenter, 'report' => $report] = $this->resolve($request, $recorder);

        $summary = $report->toFrontend()['summary'];
        $monthTitle = Carbon::createFromDate($year, $month, 1)->format('F Y');
        $filename = sprintf('medicheck-report-%d-%02d.csv', $year, $month);

        return response()->streamDownload(function () use ($summary, $healthCenter, $monthTitle): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['MediCheck Monthly Report']);
            fputcsv($out, ['Health center', $healthCenter]);
            fputcsv($out, ['Month', $monthTitle]);
            fputcsv($out, []);

            fputcsv($out, ['Summary', 'Count']);
            foreach ($this->summaryRows($summary) as $label => $value) {
                fputcsv($out, [$label, $value]);
            }

            $mostTaken = $summary['most_taken'] ?? null;
            fputcsv($out, ['Most taken medicine', $mostTaken ? "{$mostTaken['medicine']} ({$mostTaken['quantity']} pcs)" : 'None']);
            fputcsv($out, []);

            fputcsv($out, ['Date', 'Seeker', 'Medicine', 'Quantity', 'Status']);
            foreach ($summary['lines'] ?? [] as $line) {
                fputcsv($out, [$line['date'], $line['seeker'], $line['medicine'], $line['quantity'], ucfirst((string) $line['status'])]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, int>
     */
    private function summaryRows(array $summary): array
    {
        return [
            'Bookings' => (int) ($summary['bookings'] ?? 0),
            'Approved' => (int) ($summary['approved'] ?? 0),
            'Claimed' => (int) ($summary['claimed'] ?? $summary['released'] ?? 0),
            'Declined' => (int) ($summary['declined'] ?? 0),
            'Pending' => (int) ($summary['pending'] ?? 0),
            'Unclaimed' => (int) ($summary['unclaimed'] ?? $summary['cancelled'] ?? 0),
            'Low stock' => (int) ($summary['low_stock'] ?? 0),
            'Out of stock' => (int) ($summary['out_of_stock'] ?? 0),
        ];
    }

    /**
     * @return array{year: int, month: int, healthCenter: string, report: MonthlyReport}
     */
    private function resolve(Request $request, MonthlyReportRecorder $recorder): array
    {
        $healthCenter = $request->user()?->healthCenter();

        abort_unless(filled($healthCenter), 403);

        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);
        $month = (int) ($validated['month'] ?? now()->month);
        $selected = Carbon::createFromDate($year, $month, 1)->startOfMonth();

        $report = MonthlyReport::query()
            ->where('health_center', $healthCenter)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($selected->isSameMonth(now())) {
            $report = $recorder->syncCurrent($healthCenter);
        } elseif ($report === null) {
            $report = $recorder->syncMonth($healthCenter, $selected);
        }

        return ['year' => $year, 'month' => $month, 'healthCenter' => $healthCenter, 'report' => $report];
    }
}
