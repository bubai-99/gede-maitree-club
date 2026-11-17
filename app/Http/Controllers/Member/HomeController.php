<?php

namespace App\Http\Controllers\Member;

use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Family;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Member app home: what the family owes, the next event and the latest notices.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $family = self::family($request);

        return view('member.home', [
            'user' => $request->user(),
            'family' => $family,
            'dues' => $family->dues()->where('status', DueStatus::Due->value)->get(),
            'lastPayment' => $family->payments()->where('status', PaymentStatus::Confirmed->value)->first(),
            'nextEvent' => Event::query()->published()->upcoming()->first(),
            'upcoming' => Event::query()->published()->upcoming()->limit(3)->get(),
            'registered' => $family->registrations()->where('status', RegistrationStatus::Registered->value)->pluck('event_id')->all(),
            'pending' => $family->payments()->where('status', PaymentStatus::AwaitingConfirmation->value)->first(),
            'people' => $family->members()->get(),
            'notices' => Notice::query()->forMembers()->limit(3)->get(),
        ]);
    }

    /**
     * The signed-in member's family (the "member" middleware guarantees there is one).
     */
    public static function family(Request $request): Family
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->family !== null, 403);

        return $user->family;
    }
}
