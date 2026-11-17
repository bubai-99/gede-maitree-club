<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\ScanResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RegisterFamilyRequest;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\PassScan;
use App\Models\User;
use App\Passes\GateKeeper;
use App\Passes\RegistrationException;
use App\Passes\Registrations;
use App\Support\Csv;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Who is coming to an event: registrations, a CSV for the desk, attendance at the gate, and
 * registering or cancelling a family on its behalf (Puja in-charge and Admin).
 */
class EventRegistrationController extends Controller
{
    public function index(Event $event, GateKeeper $gate): View
    {
        Gate::authorize('manageRegistrations', $event);

        $registrations = $event->registrations()->with(['family', 'pass.scanner'])
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [RegistrationStatus::Registered->value])
            ->orderBy('created_at')
            ->get();

        $active = $registrations->filter(fn (EventRegistration $r): bool => $r->isActive());

        $byGate = PassScan::query()->where('event_id', $event->id)->whereNull('undone_at')
            ->where('result', ScanResult::Allowed->value)
            ->groupBy('gate')->selectRaw('gate, COUNT(*) AS scans, SUM(people) AS people')
            ->toBase()->get();

        $duplicates = PassScan::query()->where('event_id', $event->id)->whereNull('undone_at')->whereNotNull('conflict')
            ->with(['family', 'scanner'])->latest('scanned_at')->limit(50)->get();

        return view('admin.events.registrations', [
            'event' => $event,
            'registrations' => $registrations,
            'families' => $active->count(),
            'people' => (int) $active->sum('people'),
            'stats' => $gate->stats($event),
            'byGate' => $byGate,
            'conflicts' => $duplicates,
            'whatsapp' => $event->isCancelled() ? $this->whatsappLinks($event, $active->values()->all()) : [],
        ]);
    }

    public function export(Event $event): StreamedResponse
    {
        Gate::authorize('manageRegistrations', $event);

        $rows = $event->registrations()->with(['family', 'pass'])->orderBy('created_at')->get()
            ->map(fn (EventRegistration $r): array => [
                $r->family->family_no,
                $r->family->head_name,
                $r->family->phone,
                $r->family->area,
                $r->people,
                $r->status->englishLabel(),
                $r->created_at?->format('Y-m-d H:i'),
                $r->pass?->used_at?->format('Y-m-d H:i'),
                $r->pass?->entered_people,
                $r->pass?->gate,
            ]);

        return Csv::download("registrations-{$event->slug}.csv", [
            'Family no.', 'Head of family', 'Phone', 'Area', 'People', 'Status', 'Registered at', 'Entered at', 'People entered', 'Gate',
        ], $rows);
    }

    public function store(RegisterFamilyRequest $request, Event $event, Registrations $registrations): RedirectResponse
    {
        $family = $request->family();
        $user = $request->user();
        assert($family !== null && $user instanceof User);

        try {
            $registrations->register($event, $family, $request->integer('people'), $user, byCommittee: true);
        } catch (RegistrationException $e) {
            return back()->withInput()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return back()->with('status', ':family registered.')->with('status_replace', ['family' => $family->family_no]);
    }

    public function cancel(Request $request, Event $event, EventRegistration $registration, Registrations $registrations): RedirectResponse
    {
        Gate::authorize('manageRegistrations', $event);
        $user = $request->user();
        assert($user instanceof User);

        try {
            $registrations->cancel($registration, $user, byCommittee: true);
        } catch (RegistrationException $e) {
            return back()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return back()->with('status', 'Registration cancelled. The pass no longer works at the gate.');
    }

    /**
     * WhatsApp links (wa.me) with the cancellation message filled in, one per registered family.
     *
     * @param  list<EventRegistration>  $registrations
     * @return list<array{family: string, name: string, url: string}>
     */
    private function whatsappLinks(Event $event, array $registrations): array
    {
        $links = [];

        foreach ($registrations as $registration) {
            $phone = preg_replace('/\D+/', '', (string) $registration->family->phone) ?? '';

            if (strlen($phone) < 10) {
                continue;
            }

            $message = __('Namaskar. :event on :date is cancelled: :reason. Sorry for the trouble. Gede Maitree Club', [
                'event' => $event->tr('title'),
                'date' => local_date($event->starts_at, 'j F'),
                'reason' => local_text($event->cancel_reason, $event->cancel_reason_bn),
            ]);

            $links[] = [
                'family' => $registration->family->family_no,
                'name' => (string) $registration->family->tr('head_name'),
                'url' => 'https://wa.me/91'.substr($phone, -10).'?text='.rawurlencode($message),
            ];
        }

        return $links;
    }
}
