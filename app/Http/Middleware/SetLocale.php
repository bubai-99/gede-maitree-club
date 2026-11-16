<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the page language: ?lang= (used by hreflang links), then the session,
 * then the locale cookie, then the signed-in user's preference, then the default.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var list<string> $supported */
        $supported = config('club.locales');

        $fromQuery = $request->query('lang');

        if (is_string($fromQuery) && in_array($fromQuery, $supported, true)) {
            $request->session()->put('locale', $fromQuery);
        }

        $candidates = [
            $request->session()->get('locale'),
            $request->cookie('locale'),
            $request->user()?->locale,
        ];

        $locale = config('app.locale');

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && in_array($candidate, $supported, true)) {
                $locale = $candidate;
                break;
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}
