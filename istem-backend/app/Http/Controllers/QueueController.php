<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Queue;
use App\Models\Booking;
use App\Models\Instrument;
use Illuminate\Support\Facades\Mail;
use App\Mail\QueueApprovedMail;
use App\Mail\QueueRejectedMail;

class QueueController extends Controller
{
    // Legacy: basic queue creation without slot info (kept for backward compatibility)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'instrument_id' => 'required|string|exists:instruments,id',
            'user_name' => 'required|string|max:255',
            'email' => 'nullable|email',
        ]);

        // Fallback to today and a generic time slot if not provided
        $date = $request->input('date', now()->toDateString());
        $timeSlot = $request->input('time_slot', 'any');

        // Prevent duplicates for same user & slot
        $exists = Queue::where('instrument_id', $validated['instrument_id'])
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->where(function ($q) use ($validated) {
                $q->where('email', $validated['email'] ?? null)
                    ->orWhere('user_name', $validated['user_name']);
            })
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'already_in_queue',
            ], 409);
        }

        $last = Queue::where('instrument_id', $validated['instrument_id'])
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->orderBy('queue_position', 'desc')
            ->first();

        $position = $last ? $last->queue_position + 1 : 1;

        $queue = Queue::create([
            'id' => uniqid('Q'),
            'instrument_id' => $validated['instrument_id'],
            'user_name' => $validated['user_name'],
            'email' => $validated['email'] ?? null,
            'queue_position' => $position,
            'date' => $date,
            'time_slot' => $timeSlot,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $queue,
        ]);
    }

    // Modern join endpoint with explicit slot and user id
    public function join(Request $request)
    {
        $validated = $request->validate([
            'instrument_id' => 'required|string|exists:instruments,id',
            'user_id' => 'nullable|string|max:255',
            'user_name' => 'required|string|max:255',
            'email' => 'required|email',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'date' => 'nullable|date',
            'time_slot' => 'nullable|string|max:255',
        ]);

        $start = $validated['start_date'] ?? $validated['date'] ?? now()->toDateString();
        $end = $validated['end_date'] ?? $start;
        $date = $validated['date'] ?? $start;
        $timeSlot = $validated['time_slot'] ?? ($start === $end ? $start : ($start . ' → ' . $end));

        // Enforce no duplicate queue entries for same user & slot
        $exists = Queue::where('instrument_id', $validated['instrument_id'])
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->where(function ($q) use ($validated) {
                $q->where('user_id', $validated['user_id'] ?? null)
                    ->orWhere('email', $validated['email']);
            })
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'already_in_queue',
            ], 409);
        }

        $last = Queue::where('instrument_id', $validated['instrument_id'])
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->orderBy('queue_position', 'desc')
            ->first();

        $position = $last ? $last->queue_position + 1 : 1;

        $queue = Queue::create([
            'id' => uniqid('Q'),
            'instrument_id' => $validated['instrument_id'],
            'user_id' => $validated['user_id'] ?? null,
            'user_name' => $validated['user_name'],
            'email' => $validated['email'],
            'queue_position' => $position,
            'date' => $date,
            'time_slot' => $timeSlot,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'data' => $queue,
        ]);
    }

    // Get queue list per instrument (user-facing)
    public function index($instrumentId)
    {
        $queue = Queue::where('instrument_id', $instrumentId)
            ->orderBy('queue_position')
            ->get();

        return response()->json($queue);
    }

    // Admin: full queue list with instrument details
    public function adminIndex()
    {
        $queue = Queue::with('instrument')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $queue,
        ]);
    }

    public function approve(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|exists:queues,id',
        ]);

        $queue = Queue::with('instrument')->findOrFail($validated['id']);
        $queue->status = 'approved';
        $queue->save();

        // Find current booking end time for this instrument and date, if any
        $currentBooking = Booking::where('instrument_id', $queue->instrument_id)
            ->where('status', 'approved')
            ->orderBy('end_date', 'desc')
            ->first();

        $endTime = $currentBooking?->end_date;

        Mail::to($queue->email)->send(new QueueApprovedMail($queue, $endTime));

        return response()->json(['success' => true]);
    }

    public function reject(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|string|exists:queues,id',
        ]);

        $queue = Queue::with('instrument')->findOrFail($validated['id']);
        $queue->status = 'rejected';
        $queue->save();

        Mail::to($queue->email)->send(new QueueRejectedMail($queue));

        return response()->json(['success' => true]);
    }
}