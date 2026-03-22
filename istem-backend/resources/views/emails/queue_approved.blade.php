<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Queue Approved</title>
</head>
<body>
    <p>Dear {{ $queue->user_name }},</p>

    <p>You have successfully joined the queue for the instrument <strong>{{ optional($queue->instrument)->name ?? $queue->instrument_id }}</strong>.</p>

    @if($currentBookingEnd)
        <p>Your slot will be available after: <strong>{{ $currentBookingEnd }}</strong>.</p>
    @endif

    <p>
        Scheduled Date: <strong>{{ $queue->date }}</strong><br>
        Time Slot: <strong>{{ $queue->time_slot }}</strong>
    </p>

    <p>Thank you,<br>ISTEM Catalyst Center</p>
</body>
</html>
