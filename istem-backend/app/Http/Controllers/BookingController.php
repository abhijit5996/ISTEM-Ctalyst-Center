<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Booking;

// ⚠️ Only keep these if you actually created them
// use App\Services\SlotService;
// use App\Services\QueueService;
// use Illuminate\Support\Facades\Mail;
// use App\Mail\BookingApprovedMail;

class BookingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'instrument_id' => 'required|string|exists:instruments,id',
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'sometimes|in:pending,approved,rejected,completed',
            'user_type' => 'sometimes|in:student,employee',
            'identifier' => 'sometimes|string|max:255',
            'department' => 'sometimes|string|max:255',
            'program_or_school' => 'sometimes|string|max:255',
            'project_title' => 'sometimes|string|max:255',
            'confidential_project' => 'sometimes|boolean',
        ]);

        $booking = Booking::create([
            'id' => uniqid('B'),
            'instrument_id' => $validated['instrument_id'],
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'user_type' => $validated['user_type'] ?? 'student',
            'identifier' => $validated['identifier'] ?? null,
            'department' => $validated['department'] ?? null,
            'program_or_school' => $validated['program_or_school'] ?? null,
            'project_title' => $validated['project_title'] ?? null,
            'confidential_project' => $validated['confidential_project'] ?? false,
            'status' => $validated['status'] ?? 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $booking,
        ], 201);
    }

    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $booking = Booking::findOrFail($id);

            $booking->status = 'approved';
            $booking->save();

            // ❌ REMOVE if not created
            // Mail::to('user@email.com')
            //     ->send(new BookingApprovedMail($booking));

            // ❌ REMOVE if not created
            // QueueService::processQueue($booking->instrument_id);

            DB::commit();

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false]);
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

    public function reject($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->status = 'rejected';
        $booking->save();

        return response()->json(['success' => true]);
    }
}