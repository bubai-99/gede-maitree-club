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

        abort_unless($user->isCommitteeMember(), 403);

        return $next($request);
    }
}
