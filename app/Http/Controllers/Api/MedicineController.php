<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $healthCenter = $request->user()?->healthCenter();

        $medicines = Medicine::query()
            ->whereDate('expiration', '>=', now()->toDateString())
            ->when(
                filled($healthCenter),
                fn ($query) => $query->where('health_center', $healthCenter),
                fn ($query) => $query->whereRaw('0 = 1'),
            )
            ->when($request->filled('search'), function ($query) use ($request): void {
                $query->where('name', 'like', '%'.$request->string('search').'%');
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Medicine $medicine) => $medicine->toFrontend())
            ->values();

        return response()->json([
            'data' => $medicines,
        ]);
    }
}
