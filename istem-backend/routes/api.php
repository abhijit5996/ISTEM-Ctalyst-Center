<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InstrumentController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\QueueController;

Route::get('/instruments', [InstrumentController::class, 'index']);
Route::get('/instruments/{id}', [InstrumentController::class, 'show']);
Route::post('/instruments', [InstrumentController::class, 'store']);
Route::put('/instruments/{id}', [InstrumentController::class, 'update']);
Route::delete('/instruments/{id}', [InstrumentController::class, 'delete']);

Route::post('/bookings', [BookingController::class, 'store']);
Route::get('/bookings', [BookingController::class, 'index']);
Route::put('/bookings/{id}/approve', [BookingController::class, 'approve']);
Route::put('/bookings/{id}/reject', [BookingController::class, 'reject']);

Route::get('/admin/bookings', [BookingController::class, 'adminBookings']);
Route::get('/admin/dashboard', [BookingController::class, 'dashboard']);

Route::post('/queue', [QueueController::class, 'store']);
Route::get('/queue/{instrumentId}', [QueueController::class, 'index']);