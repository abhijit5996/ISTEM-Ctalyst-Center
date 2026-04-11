<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OTPMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $otp)
    {
        Log::info("🔵 [OTPMail] Constructor called", ['otp' => substr($otp, 0, 3) . '***']);
    }

    public function build(): self
    {
        Log::info("🔵 [OTPMail] build() called - preparing email");
        
        return $this
            ->subject('Your One-Time Password')
            ->view('emails.otp')
            ->with([
                'otp' => $this->otp,
            ]);
    }
}
