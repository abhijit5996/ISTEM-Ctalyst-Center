<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Slot Available</title>
</head>
<body>
    <p>Dear {{ $queue->user_name }},</p>

    <p>A slot for the instrument <strong>{{ optional($queue->instrument)->name ?? $queue->instrument_id }}</strong> is now available.</p>

    <p>
        Date: <strong>{{ $queue->date }}</strong><br>
        Time Slot: <strong>{{ $queue->time_slot }}</strong>
    </p>

    <p>Your slot is now available. Please confirm your booking at the earliest.</p>

    <p>Thank you,<br>ISTEM Catalyst Center</p>
</body>
</html>
