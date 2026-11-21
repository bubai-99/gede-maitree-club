<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use App\Passes\Registrations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Events: website content (draft → publish, as in the CMS) plus what the Puja in-charge runs:
 * registration settings, who is coming, the gate and the chanda book.
 *
 * @extends CmsController<Event>
 */
class EventController extends CmsController
{
    protected function modelClass(): string
    {
        return Event::class;
    }

    protected function routeName(): string
    {
        return 'admin.events';
    }

    protected function parameter(): string
    {
        return 'event';
    }

    protected function title(): string
    {
        return __('Events');
    }

    protected function singular(): string
    {
        return __('Event');
    }

    protected function listQuery(): Builder
    {
        return Event::query()->orderByDesc('starts_at');
    }

    protected function row(Model $record): array
    {
        if (! $record instanceof Event) {
            throw new \InvalidArgumentException('Expected an event.');
        }

        return [
            'title' => (string) $record->title,
            'meta' => local_date($record->starts_at).' · '.$record->kind->label(),
            'published' => $record->isPublished(),
        ];
    }

    public function index(): View
    {
        Gate::authorize('viewAny', Event::class);

        $events = Event::query()
            ->withCount(['registrations as families' => fn ($q) => $q->where('status', RegistrationStatus::Registered->value)])
            ->withSum(['registrations as people' => fn ($q) => $q->where('status', RegistrationStatus::Registered->value)], 'people')
            ->orderByDesc('starts_at')
            ->paginate(30);

        return view('admin.events.index', ['events' => $events]);
    }

    public function store(EventRequest $request): RedirectResponse
    {
        $event = Event::query()->create([...$request->eventData(), ...$this->statusFromButton($request)]);
        $this->savePhoto($request, $event);

        return $this->saved($request, 'admin.events.index', __('Event'));
    }

    public function update(EventRequest $request, Event $event, Registrations $registrations): RedirectResponse
    {
        $event->update([...$request->eventData(), ...$this->statusFromButton($request, $event)]);
        $this->savePhoto($request, $event);
        $registrations->issueMissingPasses($event);

        return $this->saved($request, 'admin.events.index', __('Event'));
    }

    /**
     * An event with registrations or chanda receipts is never deleted: cancel it instead.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $event = $this->record($request);

        if ($event instanceof Event && ($event->registrations()->exists() || $event->chandaEntries()->exists())) {
            return back()->with('error', __('This event has registrations or chanda receipts. Cancel it instead of deleting it.'));
        }

        return parent::destroy($request);
    }
}
