<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Queue Request Rejected</title>
</head>
<body>
    <p>Dear {{ $queue->user_name }},</p>

    <p>Your queue request for the instrument <strong>{{ optional($queue->instrument)->name ?? $queue->instrument_id }}</strong> has been rejected by the administrator.</p>

    <p>Please try booking another slot or instrument that suits your schedule.</p>

    <p>Thank you,<br>ISTEM Catalyst Center</p>
</body>
</html>
