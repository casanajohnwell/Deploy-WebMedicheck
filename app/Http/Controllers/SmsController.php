<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\SmsMessage;
use App\Models\User;
use App\Services\SmsNotifier;
use App\Services\TextBeeClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SmsController extends Controller
{
    public function index(Request $request, SmsNotifier $notifier, TextBeeClient $textBee): Response
    {
        $healthCenter = $request->user()?->healthCenter();

        abort_unless(filled($healthCenter), 403);

        $messages = SmsMessage::query()
            ->where('health_center', $healthCenter)
            ->whereNull('hidden_from_log_at')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (SmsMessage $message) => $message->toFrontend())
            ->values();

        return Inertia::render('AdminSms', [
            'messages' => $messages,
            'prefill' => $this->prefill($request, $healthCenter, $notifier),
            'smsPhone' => [
                'configured' => $textBee->configured(),
                'deviceId' => $request->user()->textbee_device_id,
                'hasDefault' => filled(config('services.textbee.device_id')),
            ],
        ]);
    }

    public function updateDevice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'device_id' => ['nullable', 'string', 'regex:/^[a-f0-9]{24}$/i'],
        ], [
            'device_id.regex' => 'The Device ID should be the 24-character code shown under your phone in the TextBee dashboard.',
        ]);

        $request->user()->forceFill([
            'textbee_device_id' => filled($validated['device_id'] ?? null) ? strtolower($validated['device_id']) : null,
        ])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SMS phone saved.']);

        return back();
    }

    public function store(Request $request, SmsNotifier $notifier, TextBeeClient $textBee): RedirectResponse
    {
        $healthCenter = $request->user()?->healthCenter();

        abort_unless(filled($healthCenter), 403);

        $validated = $request->validate([
            'recipient' => ['required', 'string', 'max:191'],
            'to' => ['required', 'string', 'max:30'],
            'type' => ['required', 'in:approval,decline,claimed,unclaimed'],
            'message' => ['required', 'string', 'max:500'],
            'booking_id' => ['nullable', 'integer'],
        ]);

        $booking = isset($validated['booking_id'])
            ? Booking::query()->where('health_center', $healthCenter)->find($validated['booking_id'])
            : null;

        $userId = $booking?->user_id ?? User::query()
            ->where('role', User::ROLE_SEEKER)
            ->get()
            ->first(fn (User $user) => $user->phone === $validated['to'])
            ?->id;

        $status = 'saved';

        if ($textBee->configured()) {
            $result = $textBee->send($validated['to'], $validated['message'], $request->user()->textbee_device_id);

            if (! $result['ok']) {
                throw ValidationException::withMessages(['to' => $result['error']]);
            }

            $status = 'sent';
        }

        $notifier->manual(
            $healthCenter,
            $validated['recipient'],
            $validated['to'],
            $validated['type'],
            $validated['message'],
            $userId,
            $booking?->id,
            $status,
        );

        Inertia::flash('toast', $status === 'sent'
            ? ['type' => 'success', 'message' => "SMS sent to {$validated['recipient']} via TextBee."]
            : ['type' => 'warning', 'message' => 'Saved to the mobile inbox only. TextBee is not set up yet, so no text was sent.']);

        return $booking ? redirect('/admin/sms') : back();
    }

    public function destroy(Request $request, SmsMessage $smsMessage): RedirectResponse
    {
        $healthCenter = $request->user()?->healthCenter();

        abort_unless(filled($healthCenter) && $smsMessage->health_center === $healthCenter, 403);

        $smsMessage->forceFill(['hidden_from_log_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'SMS removed from the log.']);

        return back();
    }

    /**
     * @return array{bookingId: int, status: string, recipient: string, to: string, type: string, message: string, alreadySent: bool}|null
     */
    private function prefill(Request $request, string $healthCenter, SmsNotifier $notifier): ?array
    {
        $booking = $request->integer('booking')
            ? Booking::query()->where('health_center', $healthCenter)->find($request->integer('booking'))
            : null;

        if ($booking === null) {
            return null;
        }

        $type = $notifier->typeFor($booking);

        return [
            'bookingId' => $booking->id,
            'status' => $booking->status,
            'recipient' => (string) $booking->seeker_name,
            'to' => (string) $booking->phone,
            'type' => $type,
            'message' => (string) $notifier->messageFor($booking),
            'alreadySent' => SmsMessage::query()
                ->where('booking_id', $booking->id)
                ->where('type', $type)
                ->exists(),
        ];
    }
}
