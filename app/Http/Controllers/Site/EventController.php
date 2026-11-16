<?php

namespace App\Http\Controllers\Site;

use App\Enums\EventKind;
use App\Http\Controllers\Controller;
use App\Models\CommitteeMember;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['kind' => ['nullable', Rule::enum(EventKind::class)]]);
        $kind = $request->enum('kind', EventKind::class);

        return view('site.events.index', [
            'kind' => $kind,
            'upcoming' => Event::query()->published()->upcoming()
                ->when($kind, fn ($q) => $q->where('kind', $kind?->value))
                ->get(),
            'past' => Event::query()->published()->past()->limit(6)->get(),
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless($event->isPublished(), 404);

        return view('site.events.show', [
            'event' => $event,
            'contact' => CommitteeMember::query()->published()->where('role', $event->contact_role)->first(),
            'more' => Event::query()->published()->upcoming()->whereKeyNot($event->getKey())->limit(3)->get(),
        ]);
    }
}
