<?php

namespace App\Http\Controllers;

use App\Models\MonthlyReport;
use App\Services\MonthlyReportRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class MonthlyReportController extends Controller
{
    public function index(Request $request, MonthlyReportRecorder $recorder): Response
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

        return Inertia::render('AdminMonthlyReport', [
            'year' => $year,
            'month' => $month,
            'report' => $report->toFrontend(),
        ]);
    }
}
