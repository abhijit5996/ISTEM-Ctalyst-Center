<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstrumentController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminAuthController;

Route::get('/instruments', [InstrumentController::class, 'index']);
Route::get('/instruments/{id}', [InstrumentController::class, 'show']);
Route::post('/instruments', [InstrumentController::class, 'store']);
Route::post('/instruments/import', [InstrumentController::class, 'import']);
Route::put('/instruments/{id}', [InstrumentController::class, 'update']);
Route::delete('/instruments/{id}', [InstrumentController::class, 'delete']);

Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings', [BookingController::class, 'index']);
Route::put('/bookings/{id}/approve', [BookingController::class, 'approve']);
Route::put('/bookings/{id}/reject', [BookingController::class, 'reject']);

Route::get('/admin/bookings', [BookingController::class, 'adminBookings']);
Route::get('/admin/dashboard', [BookingController::class, 'dashboard']);
Route::post('/lock-slot', [BookingController::class, 'lockSlot']);
Route::post('/release-lock', [BookingController::class, 'releaseLock']);

// Availability check for smart UI button logic
Route::get('/check-availability', [BookingController::class, 'checkAvailability']);

Route::post('/queue', [QueueController::class, 'store']);
Route::get('/queue/{instrumentId}', [QueueController::class, 'index']);

// Queue management
Route::post('/queue/join', [QueueController::class, 'join']);
Route::get('/admin/queue', [QueueController::class, 'adminIndex']);
Route::post('/admin/queue/approve', [QueueController::class, 'approve']);
Route::post('/admin/queue/reject', [QueueController::class, 'reject']);

// ==== User Authentication & Profile ====
Route::post('/signup', [AuthController::class, 'signup']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::get('/user/profile', [AuthController::class, 'profile']);
Route::get('/bookings/user', [AuthController::class, 'userBookings']);
Route::get('/queue/user', [AuthController::class, 'userQueue']);

// Google OAuth
Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// ==== Admin Authentication ====
Route::get('/admin/exists', [AdminAuthController::class, 'exists']);
Route::post('/admin/signup', [AdminAuthController::class, 'signup']);
Route::post('/admin/login', [AdminAuthController::class, 'login']);
Route::get('/admin/me', [AdminAuthController::class, 'me']);

Route::options('{any}', function () {
	return response()->noContent();
})->where('any', '.*');