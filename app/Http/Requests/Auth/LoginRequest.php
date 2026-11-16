<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Sign-in with a mobile number or email and a password.
 *
 * Throttling: 5 wrong tries a minute for a login from one address, and a 15-minute
 * lockout for the login after 10 wrong tries from anywhere.
 */
class LoginRequest extends FormRequest
{
    public const MAX_PER_MINUTE = 5;

    public const MAX_BEFORE_LOCKOUT = 10;

    public const LOCKOUT_SECONDS = 900;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Check the password and sign in. Returns the signed-in user.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $login = trim($this->string('login')->toString());
        $credentials = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? ['email' => Str::lower($login)]
            : ['phone' => User::normalisePhone($login)];

        if (! Auth::attempt([...$credentials, 'password' => $this->string('password')->toString()], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey(), 60);
            RateLimiter::hit($this->lockoutKey(), self::LOCKOUT_SECONDS);

            throw ValidationException::withMessages([
                'login' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->lockoutKey());

        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $limits = [
            [$this->throttleKey(), self::MAX_PER_MINUTE],
            [$this->lockoutKey(), self::MAX_BEFORE_LOCKOUT],
        ];

        foreach ($limits as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                event(new Lockout($this));

                throw ValidationException::withMessages([
                    'login' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
                ]);
            }
        }
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')->toString()).'|'.$this->ip());
    }

    public function lockoutKey(): string
    {
        return 'lockout|'.Str::transliterate(Str::lower($this->string('login')->toString()));
    }
}
