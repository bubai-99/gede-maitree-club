<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\EventRequest;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
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

    public function store(EventRequest $request): RedirectResponse
    {
        $event = Event::query()->create([...$request->eventData(), ...$this->statusFromButton($request)]);
        $this->savePhoto($request, $event);

        return $this->saved($request, 'admin.events.index', __('Event'));
    }

    public function update(EventRequest $request, Event $event): RedirectResponse
    {
        $event->update([...$request->eventData(), ...$this->statusFromButton($request, $event)]);
        $this->savePhoto($request, $event);

        return $this->saved($request, 'admin.events.index', __('Event'));
    }
}
