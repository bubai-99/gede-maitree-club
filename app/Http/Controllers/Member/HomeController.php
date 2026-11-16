<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Member app home.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('member.home', ['user' => $request->user()]);
    }
}
