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

            try {
                Mail::to($booking->user_email)->send(new BookingApprovedMail($booking));
            } catch (\Exception $mailException) {
                Log::warning('Booking approval mail send failed (sync), trying queued', ['booking_id' => $id, 'error' => $mailException->getMessage()]);
                try {
                    Mail::to($booking->user_email)->queue(new BookingApprovedMail($booking));
                } catch (\Exception $queueException) {
                    Log::error('Booking approval mail queue failed', ['booking_id' => $id, 'error' => $queueException->getMessage()]);
                }
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function reject($id)
    {
            try {
            $booking = Booking::findOrFail($id);

            $booking->status = 'rejected';
            $booking->save();

            try {
                Mail::to($booking->user_email)->send(new BookingRejectedMail($booking));
            } catch (\Exception $mailException) {
                Log::warning('Booking rejection mail send failed (sync), trying queued', ['booking_id' => $id, 'error' => $mailException->getMessage()]);
                try {
                    Mail::to($booking->user_email)->queue(new BookingRejectedMail($booking));
                } catch (\Exception $queueException) {
                    Log::error('Booking rejection mail queue failed', ['booking_id' => $id, 'error' => $queueException->getMessage()]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
        $totalInstruments = Instrument::count();
        $totalBookings = Booking::count();
        $pendingRequests = Booking::where('status', 'pending')->count();
        $approvedBookings = Booking::where('status', 'approved')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_instruments' => $totalInstruments,
                'total_bookings' => $totalBookings,
                'pending_requests' => $pendingRequests,
                'approved_bookings' => $approvedBookings,
            ],
        ]);
    }
}