<?php

namespace App\Policies;

use App\Models\ChandaArea;
use App\Models\ChandaEntry;
use App\Models\User;

/**
 * Chanda receipts. The Puja in-charge and the Treasurer (chanda.manage) see and write every
 * receipt; a volunteer (chanda.record) writes receipts only in an area assigned to them and sees
 * only the receipts they collected. Receiving cash from collectors is the Treasurer's job.
 */
class ChandaEntryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('chanda.manage') || $user->can('chanda.record');
    }

    public function view(User $user, ChandaEntry $entry): bool
    {
        return $user->can('chanda.manage')
            || ($user->can('chanda.record') && ($entry->collected_by === $user->id || $entry->recorded_by === $user->id));
    }

    /**
     * Write a receipt in this area (null: no area, e.g. at the club room).
     */
    public function recordIn(User $user, ?ChandaArea $area): bool
    {
        if ($user->can('chanda.manage')) {
            return true;
        }

        return $area !== null && $user->can('chanda.record') && $area->collector_id === $user->id;
    }

    public function cancel(User $user, ChandaEntry $entry): bool
    {
        return $user->can('chanda.manage') && ! $entry->isCancelled();
    }

    /**
     * Receive chanda cash from a collector.
     */
    public function receiveCash(User $user): bool
    {
        return $user->can('books.manage');
    }
}
