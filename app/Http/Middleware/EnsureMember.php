<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Member app screens: only people who belong to a member family.
 * A committee-only account is sent to the console instead.
 */
class EnsureMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->guest(route('member.login'));
        }

        if (! $user->isMember()) {
            if ($user->isCommitteeMember()) {
                return redirect()->route('admin.dashboard')
                    ->with('status', 'The member app is for member families. This account opens the committee console.');
            }

            abort(403);
        }

        return $next($request);
    }
}
