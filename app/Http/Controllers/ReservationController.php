<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reservation;
use App\Models\ReservationRoom;
use App\Models\Venue;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationCreated;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['venue', 'reservationRooms.room']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('email')) {
            $query->where('email', $request->email);
        }

        $reservations = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reservations
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'venue_id' => 'required|exists:venues,id',
            'event_type' => 'required|in:single,multi',
            'event_date' => 'required_if:event_type,single|date|after_or_equal:today',
            'check_in_date' => 'required_if:event_type,multi|date|after_or_equal:today',
            'check_out_date' => 'required_if:event_type,multi|date|after:check_in_date',
            'attendees' => 'required|integer|min:1',
            'needs_rooms' => 'boolean',
            'organization' => 'required|string|max:255',
            'event_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'position' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'details' => 'nullable|string',
            'rooms' => 'required_if:needs_rooms,true|array',
            'rooms.*.room_id' => 'required|exists:rooms,id',
            'rooms.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $venue = Venue::findOrFail($request->venue_id);

            // Calculate nights and days
            $nights = 0;
            $days = 1;
            
            if ($request->event_type === 'multi') {
                $checkIn = \Carbon\Carbon::parse($request->check_in_date);
                $checkOut = \Carbon\Carbon::parse($request->check_out_date);
                $nights = $checkIn->diffInDays($checkOut);
                $days = $nights;
            }

            // Calculate venue total
            $venueTotal = $venue->price * $days;

            // Calculate rooms total
            $roomsTotal = 0;
            if ($request->needs_rooms && $request->has('rooms')) {
                foreach ($request->rooms as $roomData) {
                    $room = Room::findOrFail($roomData['room_id']);
                    $roomsTotal += $room->price * $roomData['quantity'] * $nights;
                }
            }

            // Create reservation
            $reservation = Reservation::create([
                'venue_id' => $request->venue_id,
                'event_type' => $request->event_type,
                'event_date' => $request->event_type === 'single' ? $request->event_date : null,
                'check_in_date' => $request->event_type === 'multi' ? $request->check_in_date : null,
                'check_out_date' => $request->event_type === 'multi' ? $request->check_out_date : null,
                'nights' => $nights,
                'attendees' => $request->attendees,
                'needs_rooms' => $request->needs_rooms ?? false,
                'organization' => $request->organization,
                'event_name' => $request->event_name,
                'contact_person' => $request->contact_person,
                'position' => $request->position,
                'email' => $request->email,
                'phone' => $request->phone,
                'details' => $request->details,
                'venue_total' => $venueTotal,
                'rooms_total' => $roomsTotal,
                'total_amount' => $venueTotal + $roomsTotal,
                'status' => 'pending',
            ]);

            // Create reservation rooms
            if ($request->needs_rooms && $request->has('rooms')) {
                foreach ($request->rooms as $roomData) {
                    $room = Room::findOrFail($roomData['room_id']);
                    $subtotal = $room->price * $roomData['quantity'] * $nights;

                    ReservationRoom::create([
                        'reservation_id' => $reservation->id,
                        'room_id' => $room->id,
                        'quantity' => $roomData['quantity'],
                        'nights' => $nights,
                        'price_per_night' => $room->price,
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            DB::commit();

            // Load relationships
            $reservation->load(['venue', 'reservationRooms.room']);

            // Send email notification (optional)
            // Mail::to($reservation->email)->send(new ReservationCreated($reservation));

            return response()->json([
                'success' => true,
                'message' => 'Reservation created successfully',
                'data' => $reservation
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create reservation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $reservation = Reservation::with(['venue', 'reservationRooms.room'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $reservation
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,confirmed,cancelled,completed',
            'admin_notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $reservation = Reservation::findOrFail($id);
        $reservation->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reservation status updated successfully',
            'data' => $reservation
        ]);
    }

    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);
        $reservation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reservation deleted successfully'
        ]);
    }

    public function checkAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'venue_id' => 'required|exists:venues,id',
            'event_type' => 'required|in:single,multi',
            'event_date' => 'required_if:event_type,single|date',
            'check_in_date' => 'required_if:event_type,multi|date',
            'check_out_date' => 'required_if:event_type,multi|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Reservation::where('venue_id', $request->venue_id)
            ->where('status', '!=', 'cancelled');

        if ($request->event_type === 'single') {
            $query->where(function($q) use ($request) {
                $q->where('event_date', $request->event_date)
                  ->orWhere(function($q2) use ($request) {
                      $q2->where('check_in_date', '<=', $request->event_date)
                         ->where('check_out_date', '>=', $request->event_date);
                  });
            });
        } else {
            $query->where(function($q) use ($request) {
                $q->whereBetween('event_date', [$request->check_in_date, $request->check_out_date])
                  ->orWhere(function($q2) use ($request) {
                      $q2->where('check_in_date', '<', $request->check_out_date)
                         ->where('check_out_date', '>', $request->check_in_date);
                  });
            });
        }

        $conflicts = $query->exists();

        return response()->json([
            'success' => true,
            'available' => !$conflicts,
            'message' => $conflicts ? 'Venue is not available for selected dates' : 'Venue is available'
        ]);
    }
}