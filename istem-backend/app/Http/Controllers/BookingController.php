<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Booking;
use App\Models\Instrument;
use App\Models\BookingLock;
use App\Services\SlotService;
use App\Services\QueueService;
use App\Mail\BookingApprovedMail;
use App\Mail\BookingRejectedMail;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
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

            // Auto-delete expired locks before conflict check
            BookingLock::where('expires_at', '<', now())->delete();
            Log::info('Expired locks removed before booking check', ['instrument_id' => $validated['instrument_id']]);

            if (SlotService::hasConflict(
                $validated['instrument_id'], 
                $validated['start_date'], 
                $validated['end_date']
            )) {
                Log::info('Booking conflict detected', ['instrument_id' => $validated['instrument_id'], 'start_date' => $validated['start_date'], 'end_date' => $validated['end_date'], 'email' => $validated['email']]);
                return response()->json([
                    'status' => 'conflict',
                    'message' => 'slot_unavailable',
                ], 409);
            }

            BookingLock::where('instrument_id', $validated['instrument_id'])
                ->where('email', $validated['email'])
                ->where('start_date', $validated['start_date'])
                ->where('end_date', $validated['end_date'])
                ->delete();

            $booking = Booking::create([
                'id' => uniqid('B'),
                'instrument_id' => $validated['instrument_id'],
                'name' => $validated['name'],
                'user_email' => $validated['email'],
                'email' => $validated['email'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'user_type' => $validated['user_type'] ?? 'student',
                'identifier' => $validated['identifier'] ?? 'N/A',
                'department' => $validated['department'] ?? 'N/A',
                'program_or_school' => $validated['program_or_school'] ?? 'N/A',
                'project_title' => $validated['project_title'] ?? 'N/A',
                'confidential_project' => $validated['confidential_project'] ?? false,
                'status' => 'pending',
            ]);

            return response()->json([
                'success' => true,
                'data' => $booking,
            ], 201);
        });
    }

    public function lockSlot(Request $request)
    {
        $request->validate([
            'instrument_id' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'email' => 'required|email',
        ]);

        // Auto-delete expired locks before locking
        BookingLock::where('expires_at', '<', now())->delete();
        Log::info('Expired locks removed before lock attempt', ['instrument_id' => $request->instrument_id, 'email' => $request->email]);

        Log::info('Attempting to lock slot', ['instrument_id' => $request->instrument_id, 'start_date' => $request->start_date, 'end_date' => $request->end_date, 'email' => $request->email]);

        if (SlotService::hasConflict(
            $request->instrument_id,
            $request->start_date,
            $request->end_date,
            $request->email
        )) {
            Log::info('Slot lock conflict', ['instrument_id' => $request->instrument_id, 'start_date' => $request->start_date, 'end_date' => $request->end_date, 'email' => $request->email]);
            return response()->json(['status' => 'conflict'], 409);
        }

        BookingLock::create([
            'instrument_id' => $request->instrument_id,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'email' => $request->email,
            'expires_at' => now()->addMinutes(5),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Lightweight availability check used by the frontend before submitting.
     */
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'instrument_id' => 'required|string|exists:instruments,id',
            'event_date' => 'sometimes|date',
            'date' => 'sometimes|date',
            'time' => 'sometimes|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'email' => 'sometimes|email',
        ]);

        $instrumentId = $validated['instrument_id'];
        $start = $validated['start_date'] ?? $validated['date'] ?? $validated['event_date'] ?? now()->toDateString();
        $end = $validated['end_date'] ?? $start;

        // Auto-delete expired locks before checking
        BookingLock::where('expires_at', '<', now())->delete();

        $hasConflict = SlotService::hasConflict(
            $instrumentId,
            $start,
            $end,
            $validated['email'] ?? null
        );

        if ($hasConflict) {
            return response()->json([
                'available' => false,
                'status' => 'conflict',
            ], 409);
        }

        return response()->json([
            'available' => true,
            'status' => 'ok',
        ]);
    }

    public function releaseLock(Request $request)
    {
        $request->validate([
            'instrument_id' => 'required|string',
            'email' => 'required|email',
        ]);

        BookingLock::where('instrument_id', $request->instrument_id)
            ->where('email', $request->email)
            ->delete();

        return response()->json(['success' => true]);
    }

    public function approve($id)
    {
        try {
            $booking = Booking::findOrFail($id);

            $booking->status = 'approved';
            $booking->save();

            $recipient = $booking->email ?? $booking->user_email;
            $emailSent = false;

            // Send confirmation email synchronously so the
            // API call completes only after attempting email.
            try {
                if ($recipient) {
                    Mail::to($recipient)->send(new BookingApprovedMail($booking));
                    $emailSent = true;
                } else {
                    Log::warning('Booking approval email recipient missing', [
                        'booking_id' => $id,
                    ]);
                }
            } catch (\Throwable $mailException) {
                Log::error('Booking approval email failed', [
                    'booking_id' => $id,
                    'error' => $mailException->getMessage(),
                ]);
            }

            // ✅ PROCESS QUEUE WHEN SLOT RELEASED
            QueueService::processQueue($booking->instrument_id);

            return response()->json([
                'success' => true,
                'email_sent' => $emailSent,
                'message' => $emailSent
                    ? 'Booking approved and email sent successfully'
                    : 'Booking approved but email failed',
            ]);

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
        $bookings = Booking::all();
        $instruments = Instrument::all();

        // Use database-agnostic monthly aggregation
        // MySQL: DATE_FORMAT(created_at, '%m'), SQLite: strftime('%m', created_at)
        $connection = DB::connection()->getDriverName();

        if ($connection === 'mysql') {
            $monthly = Booking::selectRaw("DATE_FORMAT(created_at, '%m') as month, COUNT(*) as total")
                ->groupBy('month')
                ->get();
        } else {
            $monthly = Booking::selectRaw("strftime('%m', created_at) as month, COUNT(*) as total")
                ->groupBy('month')
                ->get();
        }

        $category = Instrument::selectRaw("category, COUNT(*) as total")
            ->groupBy('category')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'instruments' => $instruments,
                'bookings' => $bookings,
                'stats' => [
                    'total_instruments' => $instruments->count(),
                    'total_bookings' => $bookings->count(),
                    'approved_bookings' => $bookings->where('status', 'approved')->count(),
                ],
                'analytics' => [
                    'monthly_bookings' => $monthly,
                    'category_distribution' => $category,
                ],
            ]
        ]);
    }
}