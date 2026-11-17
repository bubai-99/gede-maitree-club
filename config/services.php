<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // One-time sign-in codes. Only "log" exists until the club has a DLT-registered SMS sender.
    'otp' => [
        'driver' => env('OTP_DRIVER', 'log'),
    ],

    // Online payments with Razorpay Standard Checkout. While the key ID or secret is blank the
    // member app falls back to UPI to the club's own UPI ID, confirmed by the treasurer.
    // The key ID is public (it goes to the browser); the key secret and webhook secret never do.
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'api_url' => 'https://api.razorpay.com/v1',
        // Unpaid checkouts are marked expired after this many minutes (payments:expire).
        'expire_after_minutes' => 30,
    ],

    // UPI to the club (fallback): the UPI ID and payee name in the upi://pay link and QR code.
    // Blank means the UPI ID saved in the console's club settings.
    'upi' => [
        'id' => env('CLUB_UPI_ID'),
        'name' => env('CLUB_UPI_NAME'),
    ],

];
