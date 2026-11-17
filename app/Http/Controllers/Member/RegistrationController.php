<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\RegistrationRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Passes\RegistrationException;
use App\Passes\Registrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A family registers for an event in the member app (or changes how many are coming), and can
 * cancel before the cut-off. Always for the signed-in member's own family.
 */
class RegistrationController extends Controller
{
    public function store(RegistrationRequest $request, Event $event, Registrations $registrations): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User && $user->family !== null);
        abort_unless($event->isPublished(), 404);

        try {
            $registration = $registrations->register($event, $user->family, $request->integer('people'), $user);
        } catch (RegistrationException $e) {
            return back()->withInput()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('member.events.show', $event)
            ->with('status', $registration->pass !== null ? 'You’re registered. Your family pass is ready in My passes.' : 'You’re registered.');
    }

    public function destroy(Request $request, Event $event, Registrations $registrations): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);
        abort_unless($event->isPublished() && $user->family_id !== null, 404);

        $registration = EventRegistration::query()->where('event_id', $event->id)->where('family_id', $user->family_id)->first();
        abort_if($registration === null, 404);

        try {
            $registrations->cancel($registration, $user);
        } catch (RegistrationException $e) {
            return back()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('member.events.show', $event)->with('status', 'Registration cancelled');
    }
}
