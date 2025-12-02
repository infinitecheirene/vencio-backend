<?php
// app/Http/Controllers/Api/BookingController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BookingController extends Controller
{
    /**
     * Get all bookings for authenticated user
     */
    public function index(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    /**
     * Get a specific booking
     */
    public function show(Request $request, $id)
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    /**
     * Create a new booking
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'room_name' => 'required|string|max:255',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:10',
            'price_per_night' => 'required|numeric|min:0',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Calculate nights and total price
        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);
        $nights = $checkIn->diffInDays($checkOut);
        $totalPrice = $nights * $request->price_per_night;

        // FIXED: Check for overlapping bookings
        // Two bookings overlap if: existing_start < new_end AND existing_end > new_start
        $overlappingBookings = Booking::where('room_id', $request->room_id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->exists();

        if ($overlappingBookings) {
            return response()->json([
                'success' => false,
                'message' => 'This room is not available for the selected dates.',
            ], 409);
        }

        // Create booking
        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'room_id' => $request->room_id,
            'room_name' => $request->room_name,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'guests' => $request->guests,
            'price_per_night' => $request->price_per_night,
            'total_price' => $totalPrice,
            'nights' => $nights,
            'status' => 'pending',
            'special_requests' => $request->special_requests,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $booking,
        ], 201);
    }

    /**
     * Update booking status
     */
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:confirmed,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $booking = Booking::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Check if cancellation is allowed (24 hours before check-in)
        if ($request->status === 'cancelled' && !$booking->can_cancel) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation is only allowed up to 24 hours before check-in.',
            ], 400);
        }

        $booking->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Booking status updated successfully',
            'data' => $booking,
        ]);
    }

    /**
     * Cancel booking
     */
    public function cancel(Request $request, $id)
    {
        $booking = Booking::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (!$booking->can_cancel) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation is only allowed up to 24 hours before check-in.',
            ], 400);
        }

        $booking->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully',
            'data' => $booking,
        ]);
    }

    /**
     * Check room availability
     */
    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id' => 'required|integer',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $checkIn = Carbon::parse($request->check_in);
        $checkOut = Carbon::parse($request->check_out);

        // FIXED: Check availability
        // Room is available if NO overlapping bookings exist
        $isAvailable = !Booking::where('room_id', $request->room_id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('check_in', '<', $checkOut)
            ->where('check_out', '>', $checkIn)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $isAvailable,
                'room_id' => $request->room_id,
                'check_in' => $checkIn->format('Y-m-d'),
                'check_out' => $checkOut->format('Y-m-d'),
            ],
        ]);
    }
}