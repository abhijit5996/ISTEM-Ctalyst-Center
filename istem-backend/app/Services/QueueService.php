class QueueService
{
    public static function addToQueue($instrumentId, $name, $email)
    {
        $count = Queue::where('instrument_id', $instrumentId)->count();

        return Queue::create([
            'id' => uniqid('Q'),
            'instrument_id' => $instrumentId,
            'user_name' => $name,
            'email' => $email,
            'queue_position' => $count + 1
        ]);
    }

    public static function processQueue($instrumentId)
    {
        $first = Queue::where('instrument_id', $instrumentId)
            ->orderBy('queue_position')
            ->first();

        if (!$first) return;

        // Send email
        Mail::to($first->email)->send(new QueueAvailableMail($first));

        // Remove
        $first->delete();

        // Shift queue
        Queue::where('instrument_id', $instrumentId)
            ->decrement('queue_position');
    }
}