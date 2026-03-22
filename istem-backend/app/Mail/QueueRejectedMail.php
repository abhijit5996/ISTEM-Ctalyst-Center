<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use App\Models\Queue;

class QueueRejectedMail extends Mailable
{
    public Queue $queue;

    public function __construct(Queue $queue)
    {
        $this->queue = $queue;
    }

    public function build()
    {
        return $this->subject('Queue Request Rejected')
            ->view('emails.queue_rejected');
    }
}
