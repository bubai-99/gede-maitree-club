<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelEventRequest;
use App\Models\Event;
use App\Models\User;
use App\Passes\Registrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Cancel an event and tell everyone: passes stop working, a pinned notice goes to members, and
 * the registrations page lists a WhatsApp link for each registered family.
 */
class EventCancelController extends Controller
{
    public function create(Event $event): View
    {
        Gate::authorize('cancel', $event);

        return view('admin.events.cancel', [
            'event' => $event,
            'families' => $event->registrations()->where('status', 'registered')->count(),
        ]);
    }

    public function store(CancelEventRequest $request, Event $event, Registrations $registrations): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $registrations->cancelEvent(
            $event,
            $user,
            $request->string('reason')->trim()->toString(),
            $request->filled('reason_bn') ? $request->string('reason_bn')->trim()->toString() : null,
        );

        return redirect()->route('admin.events.registrations', $event)
            ->with('status', 'Event cancelled. Members see a notice in the app; send the WhatsApp messages below.');
    }
}
