<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Medicine;
use App\Models\MonthlyReport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MonthlyReportRecorder
{
    public function syncMonth(string $healthCenter, Carbon $month): MonthlyReport
    {
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();

        $bookings = Booking::query()
            ->where('health_center', $healthCenter)
            ->whereBetween('created_at', [$start, $end])
            ->latest()
            ->get();

        $medicines = Medicine::query()
            ->where('health_center', $healthCenter)
            ->get();

        $summary = [
            'bookings' => $bookings->count(),
            'pending' => $bookings->where('status', Booking::STATUS_PENDING)->count(),
            'approved' => $bookings->where('status', Booking::STATUS_APPROVED)->count(),
            'declined' => $bookings->where('status', Booking::STATUS_DECLINED)->count(),
            'claimed' => $bookings->where('status', Booking::STATUS_CLAIMED)->count(),
            'unclaimed' => $bookings->where('status', Booking::STATUS_UNCLAIMED)->count(),
            'low_stock' => $medicines->where('status', 'low')->count(),
            'out_of_stock' => $medicines->where('status', 'out_of_stock')->count(),
            'most_taken' => $this->mostTaken($bookings),
            'lines' => $bookings->take(200)->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'date' => $booking->created_at?->format('Y-m-d H:i') ?? '',
                'seeker' => $booking->seeker_name,
                'medicine' => $booking->medicine_name,
                'quantity' => $booking->quantity,
                'status' => $booking->status,
            ])->values()->all(),
        ];

        return MonthlyReport::query()->updateOrCreate(
            [
                'health_center' => $healthCenter,
                'year' => (int) $start->year,
                'month' => (int) $start->month,
            ],
            ['summary' => $summary],
        );
    }

    public function syncCurrent(string $healthCenter): MonthlyReport
    {
        return $this->syncMonth($healthCenter, now());
    }

    /**
     * Medicine with the highest total quantity approved or claimed at the health center.
     *
     * @param Collection<int, Booking> $bookings
     * @return array{medicine: string, quantity: int, times: int}|null
     */
    private function mostTaken(Collection $bookings): ?array
    {
        $top = $bookings
            ->whereIn('status', [Booking::STATUS_APPROVED, Booking::STATUS_CLAIMED])
            ->groupBy('medicine_name')
            ->map(function ($rows, $name) {
                return [
                    'medicine' => (string) $name,
                    'quantity' => (int) $rows->sum('quantity'),
                    'times' => $rows->count(),
                ];
            })
            ->sort(function (array $left, array $right) {
                return [$right['quantity'], $right['times'], $left['medicine']]
                    <=> [$left['quantity'], $left['times'], $right['medicine']];
            })
            ->first();

        return $top;
    }
}
