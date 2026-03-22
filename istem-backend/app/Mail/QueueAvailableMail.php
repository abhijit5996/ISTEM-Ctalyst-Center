<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class QueueAvailableMail extends Mailable
{
    public $queue;

    public function __construct($queue)
    {
        $this->queue = $queue;
    }

    public function build()
    {
        return $this->subject('Slot Available')
            ->view('emails.queue_available');
    }
}
