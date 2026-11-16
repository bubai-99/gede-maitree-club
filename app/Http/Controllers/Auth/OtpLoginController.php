<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Otp\OneTimeCodes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Member sign-in with a one-time code sent to the family's mobile number.
 * The reply never says whether a number is registered.
 */
class OtpLoginController extends Controller
{
    public function __construct(private readonly OneTimeCodes $codes) {}

    public function send(Request $request): RedirectResponse
    {
        $request->validate(['phone' => ['required', 'string', 'regex:/^[0-9+\-\s]{10,16}$/']]);

        $phone = User::normalisePhone($request->string('phone')->toString());
        $key = 'otp-send|'.$phone;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'phone' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)]),
            ]);
        }

        RateLimiter::hit($key, 600);

        $user = User::query()->where('phone', $phone)->whereNotNull('family_id')->first();

        if ($user !== null) {
            $this->codes->issue($phone);
        }

        $request->session()->put('otp_phone', $phone);

        return redirect()->route('member.otp.show')
            ->with('status', 'If this number belongs to a member family, a 6-digit code is on its way.');
    }

    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_phone')) {
            return redirect()->route('member.login');
        }

        return view('auth.member-otp', ['phone' => (string) $request->session()->get('otp_phone')]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);

        $phone = (string) $request->session()->get('otp_phone', '');
        $user = $phone !== '' ? User::query()->where('phone', $phone)->whereNotNull('family_id')->first() : null;

        if ($user === null || ! $this->codes->verify($phone, $request->string('code')->toString())) {
            throw ValidationException::withMessages([
                'code' => __('That code is wrong or has expired. Ask for a new one.'),
            ]);
        }

        Auth::login($user);
        $request->session()->forget('otp_phone');

        return MemberLoginController::signedIn($request, $user, route('member.home'));
    }
}
