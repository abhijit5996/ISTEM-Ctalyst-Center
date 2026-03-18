class BookingApprovedMail extends Mailable
{
    public $booking;

    public function __construct($booking)
    {
        $this->booking = $booking;
    }

    public function build()
    {
        return $this->subject('Instrument Booking Approved')
            ->view('emails.approved');
    }
}