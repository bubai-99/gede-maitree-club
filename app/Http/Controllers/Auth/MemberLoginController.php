<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Member app sign-in (/app/login): families only.
 */
class MemberLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.member-login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        if (! $user->isMember()) {
            $this->signOut($request);

            throw ValidationException::withMessages([
                'login' => __('This account is not a member family account. Committee members sign in on the committee page.'),
            ]);
        }

        return $this->signedIn($request, $user, route('member.home'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->signOut($request);

        return redirect()->route('member.login')->with('status', 'You have signed out.');
    }

    /**
     * Start a fresh session for the signed-in user (prevents session fixation).
     */
    public static function signedIn(Request $request, User $user, string $home): RedirectResponse
    {
        $request->session()->regenerate();

        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->locale !== '' && $request->session()->missing('locale')) {
            $request->session()->put('locale', $user->locale);
        }

        return redirect()->intended($home);
    }

    private function signOut(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
