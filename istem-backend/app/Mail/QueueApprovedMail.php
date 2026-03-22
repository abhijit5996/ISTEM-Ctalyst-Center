<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\Queue;

class QueueApprovedMail extends Mailable
{
    public Queue $queue;
    public ?string $currentBookingEnd;

    public function __construct(Queue $queue, ?string $currentBookingEnd = null)
    {
        $this->queue = $queue;
        $this->currentBookingEnd = $currentBookingEnd;
    }

    public function build()
    {
        return $this->subject('Queue Approved')
            ->view('emails.queue_approved');
    }
}
