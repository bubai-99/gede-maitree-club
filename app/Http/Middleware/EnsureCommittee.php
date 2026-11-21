<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Committee console: only committee members with a committee role.
 */
class EnsureCommittee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->guest(route('admin.login'));
        }

        // Signed in to the member app (both apps share one sign-in): offer the committee sign-in
        // instead of a bare "Forbidden".
        if (! $user->isCommitteeMember()) {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
