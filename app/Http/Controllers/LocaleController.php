<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    /**
     * Switch the page language and go back to the page the visitor was on.
     * The route only accepts the supported locales.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $request->session()->put('locale', $locale);

        $request->user()?->forceFill(['locale' => $locale])->save();

        Cookie::queue(Cookie::forever('locale', $locale));

        return redirect()->to($this->safePreviousUrl($request));
    }

    /**
     * Only send the visitor back to a page on this site.
     */
    private function safePreviousUrl(Request $request): string
    {
        $previous = url()->previous();

        if (parse_url($previous, PHP_URL_HOST) !== $request->getHost() || str_contains($previous, '/lang/')) {
            return url('/');
        }

        return $previous;
    }
}
