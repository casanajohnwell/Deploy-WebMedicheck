<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SmsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $messages = SmsMessage::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['approval', 'decline'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (SmsMessage $message) => $message->toApiArray())
            ->values();

        return response()->json([
            'data' => $messages,
        ]);
    }

    public function read(Request $request, SmsMessage $sms): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless($sms->user_id === $user->id, 403);

        $sms->update(['read_at' => now()]);

        return response()->json([
            'data' => $sms->fresh()?->toApiArray(),
        ]);
    }
}
