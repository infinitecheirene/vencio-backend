<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\ContactController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Email and phone validation endpoints
Route::post('/auth/check-email', [AuthController::class, 'checkEmail']);
Route::post('/auth/check-phone', [AuthController::class, 'checkPhone']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    //bookings
    Route::prefix('bookings')->group(function () {
        Route::get('/', [BookingController::class, 'index']); // Get all user bookings
        Route::post('/', [BookingController::class, 'store']); // Create booking
        Route::get('/{id}', [BookingController::class, 'show']); // Get specific booking
        Route::patch('/{id}/status', [BookingController::class, 'updateStatus']); // Update booking status
        Route::post('/{id}/cancel', [BookingController::class, 'cancel']); // Cancel booking
        Route::post('/check-availability', [BookingController::class, 'checkAvailability']); // Check room availability
    });

    // Contact management routes (admin only)
    Route::get('/contacts', [ContactController::class, 'index']);
    Route::get('/contacts/{id}', [ContactController::class, 'show']);
    Route::patch('/contacts/{id}/status', [ContactController::class, 'updateStatus']);
    Route::delete('/contacts/{id}', [ContactController::class, 'destroy']);
});
Route::prefix('rooms')->group(function () {
    Route::get('/', [RoomController::class, 'index']);
    Route::post('/', [RoomController::class, 'store']);
    Route::get('/{id}', [RoomController::class, 'show']);
    Route::post('/{id}', [RoomController::class, 'update']); // POST for form-data with images
    Route::delete('/{id}', [RoomController::class, 'destroy']);
});

// CONTACT FORM API ROUTES
Route::post('/contact', [ContactController::class, 'store']);

// Admin routes (optional, for viewing contacts)
Route::get('/contacts', [ContactController::class, 'index']);
Route::get('/contacts/{contact}', [ContactController::class, 'show']);
Route::patch('/contacts/{contact}/status/{status}', [ContactController::class, 'updateStatus']);
Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);
