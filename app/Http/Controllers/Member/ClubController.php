<?php

namespace App\Http\Controllers\Member;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Family;
use App\Models\Notice;
use App\Models\Pass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Events (upcoming, my passes, past), registering the family, and notices inside the member app
 * (members-only notices included). Members only ever see their own family's registrations and
 * passes.
 */
class ClubController extends Controller
{
    public function events(Request $request): View
    {
        $family = $this->family($request);
        $upcoming = Event::query()->published()->upcoming()->get();

        return view('member.events.index', [
            'tab' => 'upcoming',
            'upcoming' => $upcoming,
            'registrations' => $this->registrationsFor($family, $upcoming->modelKeys()),
            'passCount' => $this->activePasses($family)->count(),
        ]);
    }

    public function passes(Request $request): View
    {
        $family = $this->family($request);
        $passes = $this->activePasses($family);

        return view('member.events.index', [
            'tab' => 'passes',
            'passes' => $passes,
            'family' => $family,
            'passCount' => $passes->count(),
        ]);
    }

    public function past(Request $request): View
    {
        $family = $this->family($request);
        $past = Event::query()->published()->past()->limit(20)->get();

        return view('member.events.index', [
            'tab' => 'past',
            'past' => $past,
            'registrations' => $this->registrationsFor($family, $past->modelKeys()),
            'passCount' => $this->activePasses($family)->count(),
        ]);
    }

    public function event(Request $request, Event $event): View
    {
        abort_unless($event->isPublished(), 404);
        $family = $this->family($request);

        $registration = $family !== null
            ? EventRegistration::query()->where('event_id', $event->id)->where('family_id', $family->id)->with('pass')->first()
            : null;

        return view('member.events.show', [
            'event' => $event,
            'family' => $family,
            'registration' => $registration,
            'seatsLeft' => $event->seatsLeft(),
        ]);
    }

    public function pass(Request $request, Pass $pass): View
    {
        // Only the family's own pass (checked here, not by a policy, so no role can widen it).
        abort_unless($pass->family_id === $this->family($request)?->id, 404);
        $pass->load(['event', 'family']);

        return view('member.events.pass', ['pass' => $pass]);
    }

    public function notices(): View
    {
        return view('member.notices.index', [
            'notices' => Notice::query()->forMembers()->paginate(15),
        ]);
    }

    public function notice(Notice $notice): View
    {
        abort_unless($notice->isPublished(), 404);

        return view('member.notices.show', ['notice' => $notice]);
    }

    private function family(Request $request): ?Family
    {
        $user = $request->user();

        return $user instanceof User ? $user->family : null;
    }

    /**
     * The family's registrations for these events, by event id.
     *
     * @param  array<int, mixed>  $eventIds
     * @return Collection<int, EventRegistration>
     */
    private function registrationsFor(?Family $family, array $eventIds): Collection
    {
        if ($family === null || $eventIds === []) {
            return collect();
        }

        return EventRegistration::query()->where('family_id', $family->id)->whereIn('event_id', $eventIds)
            ->where('status', RegistrationStatus::Registered->value)->get()->keyBy('event_id');
    }

    /**
     * Passes the family can still use: active, for an event that is not over.
     *
     * @return Collection<int, Pass>
     */
    private function activePasses(?Family $family): Collection
    {
        if ($family === null) {
            return collect();
        }

        return Pass::query()->where('family_id', $family->id)->where('status', 'active')
            ->whereHas('event', fn ($q) => $q->published()->whereNull('cancelled_at')->where('starts_at', '>=', now()->startOfDay()))
            ->with('event')->get()->sortBy(fn (Pass $pass) => $pass->event->starts_at)->values();
    }
}
