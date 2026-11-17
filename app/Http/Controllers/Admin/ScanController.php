<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScanRequest;
use App\Http\Requests\Admin\ScanSyncRequest;
use App\Http\Requests\Admin\ScanUpdateRequest;
use App\Models\Event;
use App\Models\PassScan;
use App\Models\User;
use App\Passes\GateException;
use App\Passes\GateKeeper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The gate scanner (/admin/scan): Volunteer, Puja in-charge and Admin. The page is a plain ES
 * module (resources/js/scan.js) that downloads the event's pass list for offline use, checks
 * scans with the server when online, and queues them on the phone when not.
 */
class ScanController extends Controller
{
    public function page(Request $request): View
    {
        $user = $request->user();
        assert($user instanceof User);

        $events = Event::query()->where('pass_required', true)->whereNull('cancelled_at')
            ->where('starts_at', '>=', now()->subDay()->startOfDay())
            ->orderBy('starts_at')->get()
            ->filter(fn (Event $event): bool => $user->can('scan', $event))->values();

        $chosen = $request->string('event')->toString();
        $event = $events->firstWhere('slug', $chosen) ?? $events->first();

        return view('admin.scan', ['events' => $events, 'event' => $event]);
    }

    /**
     * The pass list for offline checking (hashes, family numbers, head counts) and the counts.
     */
    public function passes(Event $event, GateKeeper $gate): JsonResponse
    {
        Gate::authorize('scan', $event);

        return response()->json([
            'event' => ['id' => $event->id, 'slug' => $event->slug, 'cancelled' => $event->isCancelled()],
            'generated_at' => now()->toIso8601String(),
            'stats' => $gate->stats($event),
            'passes' => $gate->offlineList($event),
        ])->header('Cache-Control', 'no-store');
    }

    public function store(ScanRequest $request, Event $event, GateKeeper $gate): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        try {
            $scan = $gate->scan($event, $user, $request->scanData());
        } catch (GateException $e) {
            return response()->json(['message' => $e->translated()], 422);
        }

        return response()->json(['scan' => $gate->present($scan), 'stats' => $gate->stats($event)]);
    }

    public function update(ScanUpdateRequest $request, PassScan $scan, GateKeeper $gate): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        try {
            $scan = $request->boolean('let_in')
                ? $gate->letBackIn($scan, $user)
                : $gate->setPeople($scan, $user, $request->integer('people'));
        } catch (GateException $e) {
            return response()->json(['message' => $e->translated()], 422);
        }

        return response()->json(['scan' => $gate->present($scan), 'stats' => $gate->stats($scan->event)]);
    }

    public function destroy(Request $request, PassScan $scan, GateKeeper $gate): JsonResponse
    {
        Gate::authorize('scan', $scan->event);
        $user = $request->user();
        assert($user instanceof User);

        try {
            $scan = $gate->undo($scan, $user);
        } catch (GateException $e) {
            return response()->json(['message' => $e->translated()], 422);
        }

        return response()->json(['scan' => $gate->present($scan), 'stats' => $gate->stats($scan->event)]);
    }

    /**
     * Upload what the phone did while offline. Each change gets its own answer; duplicates
     * (a pass let in twice) are listed for the volunteer.
     */
    public function sync(ScanSyncRequest $request, Event $event, GateKeeper $gate): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $results = $gate->sync($event, $user, $request->operations());

        return response()->json([
            'results' => $results,
            'duplicates' => array_values(array_filter($results, fn (array $r): bool => ($r['conflict'] ?? null) !== null)),
            'stats' => $gate->stats($event),
        ]);
    }

    /**
     * End of shift: this volunteer's scans at this event since the shift began.
     */
    public function shift(Request $request, Event $event, GateKeeper $gate): JsonResponse
    {
        Gate::authorize('scan', $event);
        $user = $request->user();
        assert($user instanceof User);

        $request->validate(['since' => ['nullable', 'date']]);
        $since = $request->filled('since') ? Carbon::parse($request->string('since')->toString()) : now()->startOfDay();

        return response()->json(['shift' => $gate->shift($event, $user, $since->max(now()->subDay()))]);
    }
}
