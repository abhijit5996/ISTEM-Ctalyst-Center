<html>
<head><meta charset="utf-8"></head>
<body>
  <h1>Booking Rejected</h1>
  <p>Hi {{ $booking->name }},</p>
  <p>We are sorry to inform you that your booking request was rejected.</p>
  <p>Instrument: {{ optional($booking->instrument)->name ?? $booking->instrument_id }}</p>
  <p>Booking Period: {{ $booking->start_date }} → {{ $booking->end_date }}</p>
  <p>Status: {{ ucfirst($booking->status) }}</p>
  <p>Please contact admin for more details.</p>
</body>
</html>
