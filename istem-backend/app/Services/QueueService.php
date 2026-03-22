<?php

namespace App\Services;

use App\Models\Queue;
use Illuminate\Support\Facades\Mail;
use App\Mail\QueueAvailableMail;

class QueueService
{
    public static function addToQueue(string $instrumentId, string $name, string $email, ?string $userId, string $date, string $timeSlot): Queue
    {
        $count = Queue::where('instrument_id', $instrumentId)
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->count();

        return Queue::create([
            'id' => uniqid('Q'),
            'instrument_id' => $instrumentId,
            'user_id' => $userId,
            'user_name' => $name,
            'email' => $email,
            'queue_position' => $count + 1,
            'date' => $date,
            'time_slot' => $timeSlot,
            'status' => 'pending',
        ]);
    }

    /**
     * Notify the next user in the queue when a slot becomes available.
     */
    public static function processQueue(string $instrumentId, ?string $date = null, ?string $timeSlot = null): void
    {
        $query = Queue::where('instrument_id', $instrumentId)
            ->where('status', 'pending');

        if ($date !== null) {
            $query->where('date', $date);
        }

        if ($timeSlot !== null) {
            $query->where('time_slot', $timeSlot);
        }

        $first = $query->orderBy('queue_position')->first();

        if (!$first) {
            return;
        }

        dispatch(function () use ($first) {
            Mail::to($first->email)->send(new QueueAvailableMail($first));
        });

        // Mark as approved / notified and keep history
        $first->status = 'approved';
        $first->save();

        // Reorder remaining queue positions for this slot
        Queue::where('instrument_id', $instrumentId)
            ->when($date, fn ($q) => $q->where('date', $date))
            ->when($timeSlot, fn ($q) => $q->where('time_slot', $timeSlot))
            ->where('queue_position', '>', $first->queue_position)
            ->decrement('queue_position');
    }
}