<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Committee console sign-in (/admin/login): committee members with a role only.
 */
class CommitteeLoginController extends Controller
{
    public function create(): View
    {
        return view('auth.committee-login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        if (! $user->isCommitteeMember()) {
            $this->signOut($request);

            throw ValidationException::withMessages([
                'login' => __('This account has no committee role. Members sign in to the member app.'),
            ]);
        }

        return MemberLoginController::signedIn($request, $user, route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->signOut($request);

        return redirect()->route('admin.login')->with('status', 'You have signed out.');
    }

    private function signOut(Request $request): void
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
