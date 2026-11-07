<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Services\MonthlyReportRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MedicineController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $medicine = Medicine::query()->create($this->payload($request, Medicine::CATEGORIES));
        app(MonthlyReportRecorder::class)->syncCurrent($medicine->health_center);

        return back();
    }

    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $this->ensureSameCenter($request, $medicine);
        $medicine->update($this->payload($request, [...Medicine::CATEGORIES, $medicine->category]));
        app(MonthlyReportRecorder::class)->syncCurrent($medicine->health_center);

        return back();
    }

    public function destroy(Request $request, Medicine $medicine): RedirectResponse
    {
        $this->ensureSameCenter($request, $medicine);
        $center = $medicine->health_center;
        $medicine->delete();
        app(MonthlyReportRecorder::class)->syncCurrent($center);

        return back();
    }

    private function ensureSameCenter(Request $request, Medicine $medicine): void
    {
        abort_unless($request->user()?->healthCenter() === $medicine->health_center, 403);
    }

    /**
     * @param  list<string>  $categories
     * @return array{name: string, category: string, health_center: string, quantity: int, unit: string, expiration: string, status: string}
     */
    private function payload(Request $request, array $categories): array
    {
        $healthCenter = $request->user()?->healthCenter();

        abort_unless(filled($healthCenter), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'category' => ['required', 'string', Rule::in($categories)],
            'healthCenter' => ['nullable', 'string', 'max:191'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'string', 'max:50'],
            'expiration' => ['required', 'date'],
            'status' => ['required', 'in:available,low,out_of_stock'],
        ]);

        return [
            'name' => $validated['name'],
            'category' => $validated['category'],
            'health_center' => $healthCenter,
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit'],
            'expiration' => $validated['expiration'],
            'status' => $validated['status'],
        ];
    }
}
