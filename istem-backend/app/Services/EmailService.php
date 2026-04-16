<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EmailService
{
    public static function sendOTP(string $to, string $otp): bool
    {
        try {
            $resend = \Resend::client(env('RESEND_API_KEY'));

            $response = $resend->emails->send([
                'from' => 'onboarding@resend.dev',
                'to' => [$to],
                'subject' => 'Your OTP Code',
                'html' => "
                    <h2>Your OTP Code</h2>
                    <p>Your OTP is:</p>
                    <h1>{$otp}</h1>
                    <p>This OTP will expire in 5 minutes.</p>
                ",
            ]);

            Log::info('Resend email sent', ['response' => $response]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Resend email failed', ['error' => $e->getMessage()]);
            throw new \Exception('otp_send_failed');
        }
    }
}
