<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Booking;
use App\Models\Instrument;
use App\Services\SlotService;
use App\Mail\BookingApprovedMail;
use App\Mail\BookingRejectedMail;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'instrument_id' => 'required|string|exists:instruments,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_type' => 'sometimes|in:student,employee',
            'identifier' => 'sometimes|string|max:255',
            'department' => 'sometimes|string|max:255',
            'program_or_school' => 'sometimes|string|max:255',
            'project_title' => 'sometimes|string|max:255',
            'confidential_project' => 'sometimes|boolean',
        ]);

        if (SlotService::hasConflict($validated['instrument_id'], $validated['start_date'], $validated['end_date'])) {
            return response()->json([
                'status' => 'conflict',
                'message' => 'slot_already_booked',
            ], 409);
        }

        $booking = Booking::create([
            'id' => uniqid('B'),
            'instrument_id' => $validated['instrument_id'],
            'name' => $validated['name'],
            'user_email' => $validated['email'],
            'email' => $validated['email'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'user_type' => $validated['user_type'] ?? 'student',
            'identifier' => $validated['identifier'] ?? null,
            'department' => $validated['department'] ?? null,
            'program_or_school' => $validated['program_or_school'] ?? null,
            'project_title' => $validated['project_title'] ?? null,
            'confidential_project' => $validated['confidential_project'] ?? false,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ], 201);
    }

    public function approve($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            $booking->status = 'approved';
            $booking->save();

            $recipient = $booking->email ?? $booking->user_email;

            // ✅ NON-BLOCKING EMAIL
            dispatch(function () use ($recipient, $booking) {
                Mail::to($recipient)->send(new BookingApprovedMail($booking));
            });

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Booking approval failed', ['booking_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    public function reject($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            $booking->status = 'rejected';
            $booking->save();

            $recipient = $booking->email ?? $booking->user_email;

            dispatch(function () use ($recipient, $booking) {
                Mail::to($recipient)->send(new BookingRejectedMail($booking));
            });

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Booking rejection failed', ['booking_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    public function index()
    {
        $bookings = Booking::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    public function adminBookings()
    {
        $bookings = Booking::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    public function dashboard()
    {
            $instruments = Instrument::with('bookings')->get();
        $bookings = Booking::all();
        $totalInstruments = $instruments->count();
        $totalBookings = $bookings->count();
        $pendingRequests = $bookings->where('status', 'pending')->count();
        $approvedBookings = $bookings->where('status', 'approved')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'instruments' => $instruments,
                'bookings' => $bookings,
                'stats' => [
                    'total_instruments' => $totalInstruments,
                    'total_bookings' => $totalBookings,
                    'pending_requests' => $pendingRequests,
                    'approved_bookings' => $approvedBookings,
                ],
            ],
        ]);
    }
}