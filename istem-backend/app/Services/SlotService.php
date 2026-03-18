class SlotService
{
    public static function hasConflict($instrumentId, $start, $end)
    {
        return Booking::where('instrument_id', $instrumentId)
            ->where('status', 'approved')
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<=', $end)
                      ->where('end_date', '>=', $start);
            })
            ->exists();
    }
}