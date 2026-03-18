<html>
<head><meta charset="utf-8"></head>
<body>
  <h1>Booking Approved</h1>
  <p>Hi {{ $booking->name }},</p>
  <p>Your booking request has been approved.</p>
  <p>Instrument: {{ optional($booking->instrument)->name ?? $booking->instrument_id }}</p>
  <p>Booking Period: {{ $booking->start_date }} → {{ $booking->end_date }}</p>
  <p>Status: {{ ucfirst($booking->status) }}</p>
  <p>Thank you for using the booking system.</p>
</body>
</html>
