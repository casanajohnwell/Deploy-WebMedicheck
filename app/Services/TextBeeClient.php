<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TextBeeClient
{
    public const NO_REPLY_NOTICE = 'This is an automated message. Please do not reply.';

    public function configured(): bool
    {
        return filled(config('services.textbee.key'));
    }

    /**
     * @return array{ok: bool, error: string|null}
     */
    public function send(string $phone, string $message, ?string $deviceId = null): array
    {
        $number = static::toInternational($phone);

        if ($number === null) {
            return ['ok' => false, 'error' => 'The mobile number is not a valid Philippine number.'];
        }

        $payload = array_filter([
            'recipients' => [$number],
            'message' => rtrim($message)."\n\n".self::NO_REPLY_NOTICE,
            'deviceId' => $deviceId ?: config('services.textbee.device_id'),
        ]);

        try {
            $response = Http::withHeader('x-api-key', (string) config('services.textbee.key'))
                ->acceptJson()
                ->timeout(15)
                ->post(rtrim((string) config('services.textbee.url'), '/').'/gateway/send-sms', $payload);
        } catch (Throwable $exception) {
            Log::warning('TextBee request failed.', ['error' => $exception->getMessage()]);

            return ['ok' => false, 'error' => 'Could not reach TextBee. Check the internet connection.'];
        }

        if ($response->successful()) {
            return ['ok' => true, 'error' => null];
        }

        Log::warning('TextBee rejected the SMS.', ['status' => $response->status(), 'body' => $response->body()]);

        return ['ok' => false, 'error' => match ($response->status()) {
            401 => 'TextBee rejected the API key. Check TEXTBEE_API_KEY in .env.',
            404 => 'TextBee device not found. Check the SMS phone Device ID.',
            429 => 'TextBee daily or monthly SMS limit reached.',
            400 => 'TextBee could not send. Check the Device ID, and make sure the SMS phone is online and the TextBee app is running.',
            default => 'TextBee could not send the SMS right now.',
        }];
    }

    public static function toInternational(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (preg_match('/^09\d{9}$/', $digits)) {
            return '+63'.substr($digits, 1);
        }

        if (preg_match('/^9\d{9}$/', $digits)) {
            return '+63'.$digits;
        }

        if (preg_match('/^639\d{9}$/', $digits)) {
            return '+'.$digits;
        }

        return null;
    }
}
