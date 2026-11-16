<?php

namespace App\Support\Otp;

use App\Contracts\OtpSender;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

/**
 * Six-digit sign-in codes: valid for 10 minutes, five tries, stored hashed in the cache.
 */
class OneTimeCodes
{
    public const TTL_SECONDS = 600;

    public const MAX_TRIES = 5;

    public function __construct(private readonly OtpSender $sender) {}

    public function issue(string $phone): void
    {
        $code = (string) random_int(100000, 999999);

        Cache::put($this->key($phone), ['hash' => Hash::make($code), 'tries' => 0], self::TTL_SECONDS);

        $this->sender->send($phone, $code);
    }

    public function verify(string $phone, string $code): bool
    {
        $key = $this->key($phone);
        $entry = Cache::get($key);

        if (! is_array($entry) || $entry['tries'] >= self::MAX_TRIES) {
            Cache::forget($key);

            return false;
        }

        if (! Hash::check($code, $entry['hash'])) {
            Cache::put($key, ['hash' => $entry['hash'], 'tries' => $entry['tries'] + 1], self::TTL_SECONDS);

            return false;
        }

        Cache::forget($key);

        return true;
    }

    private function key(string $phone): string
    {
        return 'otp:'.hash('sha256', $phone);
    }
}
