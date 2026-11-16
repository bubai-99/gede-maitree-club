<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Support\QrCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The family's own records: dues, receipts, member cards and profile.
 */
class AccountController extends Controller
{
    public function dues(Request $request): View
    {
        $family = HomeController::family($request);

        return view('member.dues', [
            'family' => $family,
            'dues' => $family->dues()->orderByDesc('period')->limit(24)->get(),
            'outstanding' => $family->outstanding(),
        ]);
    }

    public function receipts(Request $request): View
    {
        $family = HomeController::family($request);

        return view('member.receipts.index', [
            'payments' => $family->payments()->whereNotNull('receipt_no')->paginate(20),
        ]);
    }

    public function receipt(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        return view('member.receipts.show', [
            'payment' => $payment->load(['dues', 'family', 'receiver']),
        ]);
    }

    public function card(Request $request): View
    {
        $family = HomeController::family($request);
        $members = $family->members;

        return view('member.card', [
            'family' => $family,
            'members' => $members,
            'codes' => $members->mapWithKeys(fn ($member) => [$member->id => $member->member_no ? QrCode::svg(QrCode::memberCode($member), 220) : null]),
        ]);
    }

    public function profile(Request $request): View
    {
        $family = HomeController::family($request);

        return view('member.profile', [
            'user' => $request->user(),
            'family' => $family,
            'members' => $family->members,
        ]);
    }
}
