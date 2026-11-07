<?php

use App\Http\Controllers\AdminLocationController;
use App\Http\Controllers\AdminNoticeController;
use App\Http\Controllers\AdminRegistrationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\MonthlyReportController;
use App\Http\Controllers\PasswordResetRequestController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\SuperAdminProfileController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::post('forgot-password', [PasswordResetRequestController::class, 'sendCode'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.email');
Route::post('forgot-password/verify', [PasswordResetRequestController::class, 'verifyCode'])
    ->middleware(['guest', 'throttle:10,1'])
    ->name('password.verify-code');
Route::post('forgot-password/new-password', [PasswordResetRequestController::class, 'updatePassword'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('password.code-update');
Route::post('forgot-password/restart', [PasswordResetRequestController::class, 'restart'])
    ->middleware('guest')
    ->name('password.restart');

Route::get('superadmin', [SuperAdminController::class, 'create'])->name('superadmin.login');
Route::post('superadmin', [SuperAdminController::class, 'store'])->name('superadmin.login.store');

Route::middleware('superadmin')->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('accounts', [SuperAdminController::class, 'accounts'])->name('accounts');
    Route::get('users', [SuperAdminController::class, 'users'])->name('users');
    Route::get('accounts/walk-in', [SuperAdminController::class, 'walkIn'])->name('accounts.walk-in');
    Route::post('accounts/walk-in', [SuperAdminController::class, 'storeWalkIn'])->name('accounts.walk-in.store');
    Route::put('accounts/{user}', [SuperAdminController::class, 'updateAccount'])->name('accounts.update');
    Route::delete('accounts/{user}', [SuperAdminController::class, 'destroyAccount'])->name('accounts.destroy');
    Route::post('accounts/{user}/approve', [SuperAdminController::class, 'approveAccount'])->name('accounts.approve');
    Route::post('accounts/{user}/decline', [SuperAdminController::class, 'declineAccount'])->name('accounts.decline');
    Route::delete('rejected/{declinedAdmin}', [SuperAdminController::class, 'destroyDeclined'])->name('rejected.destroy');
    Route::post('notices/{notice}/read', [AdminNoticeController::class, 'read'])->name('notices.read');
    Route::get('profile', [SuperAdminProfileController::class, 'show'])->name('profile');
    Route::post('profile/email', [SuperAdminProfileController::class, 'sendEmailCode'])
        ->middleware('throttle:5,1')
        ->name('profile.email');
    Route::post('profile/email/verify', [SuperAdminProfileController::class, 'verifyEmailCode'])
        ->middleware('throttle:10,1')
        ->name('profile.email.verify');
    Route::post('profile/email/cancel', [SuperAdminProfileController::class, 'cancelEmailChange'])->name('profile.email.cancel');
    Route::put('profile/password', [SuperAdminProfileController::class, 'updatePassword'])
        ->middleware('throttle:6,1')
        ->name('profile.password');
    Route::post('logout', [SuperAdminController::class, 'destroy'])->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::get('register', [AdminRegistrationController::class, 'create'])->name('register');
    Route::post('register', [AdminRegistrationController::class, 'store'])->name('register.store');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::redirect('dashboard', '/admin/dashboard')->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::redirect('select-location', '/admin/dashboard')->name('select-location');
        Route::put('location', [AdminLocationController::class, 'update'])->name('location.update');
        Route::post('notices/{notice}/read', [AdminNoticeController::class, 'read'])->name('notices.read');
        Route::inertia('dashboard', 'AdminDashboard')->name('dashboard');
        Route::inertia('metrics', 'AdminMetrics')->name('metrics');
        Route::get('reports', [MonthlyReportController::class, 'index'])->name('reports');
        Route::inertia('bookings/approval', 'AdminBooking')->name('bookings.approval');
        Route::inertia('bookings/history', 'AdminBookingHistory')->name('bookings.history');
        Route::put('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::inertia('inventory', 'AdminInventory')->name('inventory');
        Route::post('inventory', [MedicineController::class, 'store'])->name('inventory.store');
        Route::put('inventory/{medicine}', [MedicineController::class, 'update'])->name('inventory.update');
        Route::delete('inventory/{medicine}', [MedicineController::class, 'destroy'])->name('inventory.destroy');
        Route::get('sms', [SmsController::class, 'index'])->name('sms');
        Route::post('sms', [SmsController::class, 'store'])->name('sms.store');
        Route::put('sms/device', [SmsController::class, 'updateDevice'])->name('sms.device');
        Route::delete('sms/{smsMessage}', [SmsController::class, 'destroy'])->name('sms.destroy');
    });
});

require __DIR__.'/settings.php';
