<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Events are website content (every committee role may look), but the Puja in-charge runs them:
 * the Admin, the Website editor and the Puja in-charge may create, edit and publish events.
 * Registrations, cancelling and the gate belong to the Puja in-charge (and the Admin, who passes
 * every check); the chanda book also to the Treasurer; volunteers record chanda in their own area.
 */
class EventPolicy extends WebsiteContentPolicy
{
    public function create(User $user): bool
    {
        return $user->can('website.edit') || $user->can('events.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can('website.edit') || $user->can('events.manage');
    }

    public function publish(User $user, Model $model): bool
    {
        return $user->can('website.edit') || $user->can('events.manage');
    }

    /**
     * Only an event nobody registered for and with no chanda can be deleted (cancel it instead).
     */
    public function delete(User $user, Model $model): bool
    {
        if (! $model instanceof Event || $model->registrations()->exists() || $model->chandaEntries()->exists()) {
            return false;
        }

        return $user->can('website.edit') || $user->can('events.manage');
    }

    /**
     * Who is coming, the CSV, attendance, registering or cancelling a family at the desk.
     */
    public function manageRegistrations(User $user, Event $event): bool
    {
        return $user->can('events.manage') || $user->can('passes.manage');
    }

    public function cancel(User $user, Event $event): bool
    {
        return $user->can('events.manage') && ! $event->isCancelled();
    }

    /**
     * Use the gate scanner for this event.
     */
    public function scan(User $user, Event $event): bool
    {
        return $user->can('passes.scan') && $event->pass_required;
    }

    /**
     * Open the event's chanda book (volunteers see only what they collected).
     */
    public function viewChanda(User $user, Event $event): bool
    {
        return $event->chanda_box && ($user->can('chanda.manage') || $user->can('chanda.record'));
    }

    /**
     * Areas, collectors, sponsors, budget and what the website shows.
     */
    public function manageChanda(User $user, Event $event): bool
    {
        return $event->chanda_box && $user->can('chanda.manage');
    }
}
