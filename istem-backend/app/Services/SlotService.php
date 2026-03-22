<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingLock;
use Illuminate\Support\Facades\Log;

class SlotService
{
    public static function hasConflict($instrumentId, $start, $end, $excludeEmail = null)
    {
        Log::info('Slot conflict check', [
            'instrument_id' => $instrumentId,
            'start_date' => $start,
            'end_date' => $end,
            'exclude_email' => $excludeEmail,
        ]);

        // Approved booking overlap
        $bookingConflict = Booking::where('instrument_id', $instrumentId)
            ->where('status', 'approved')
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end)
                      ->where('end_date', '>=', $start);
            })
            ->exists();

        // Active lock overlap
        $lockQuery = BookingLock::where('instrument_id', $instrumentId)
            ->where('expires_at', '>', now())
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end)
                      ->where('end_date', '>=', $start);
            });

        if (!empty($excludeEmail)) {
            $lockQuery->where('email', '!=', $excludeEmail);
        }

        $lockConflict = $lockQuery->exists();

        return $bookingConflict || $lockConflict;
    }
}
