<?php

namespace App\Support\Otp;

use App\Contracts\OtpSender;
use Illuminate\Support\Facades\Log;

/**
 * Development driver: writes the code to the application log instead of sending an SMS.
 */
class LogOtpSender implements OtpSender
{
    public function send(string $phone, string $code): void
    {
        Log::info('One-time sign-in code', [
            'phone' => str_repeat('•', 6).substr($phone, -4),
            'code' => $code,
        ]);
    }
}
