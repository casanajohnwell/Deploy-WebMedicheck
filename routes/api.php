<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\MedicineController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\SmsController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'sanctum' => true,
    ]);
});

Route::middleware('throttle:api-auth')->group(function () {
    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('throttle:10,1')->group(function () {
    Route::post('/forgot-password', [PasswordResetController::class, 'sendCode']);
    Route::post('/forgot-password/verify', [PasswordResetController::class, 'verifyCode']);
    Route::post('/forgot-password/reset', [PasswordResetController::class, 'reset']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/user', [AuthController::class, 'update']);
    Route::patch('/me', [AuthController::class, 'update']);
    Route::put('/me/password', [AuthController::class, 'password']);
    Route::post('/me/avatar', [AuthController::class, 'avatar']);

    Route::get('/medicines', [MedicineController::class, 'index']);
    Route::get('/sms', [SmsController::class, 'index']);
    Route::post('/sms/{sms}/read', [SmsController::class, 'read']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
});
