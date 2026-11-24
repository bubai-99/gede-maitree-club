<?php

namespace App\Support;

use App\Chanda\ChandaBook;
use App\Enums\DueStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublishStatus;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\Family;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The console's bell: what is waiting for the person signed in (the same checks as the
 * dashboard's "Needs you today", each only for the roles that can act on it), and the latest
 * notices. Worked out once per page.
 */
final class AdminAlerts
{
    /** @var array<int, array{items: list<array{icon: string, text: string, url: string}>, notices: Collection<int, Notice>}> */
    private array $cache = [];

    public function __construct(private readonly ChandaBook $chandaBook) {}

    /**
     * @return array{items: list<array{icon: string, text: string, url: string}>, notices: Collection<int, Notice>}
     */
    public function for(User $user): array
    {
        return $this->cache[$user->id] ??= $this->build($user);
    }

    /**
     * @return array{items: list<array{icon: string, text: string, url: string}>, notices: Collection<int, Notice>}
     */
    private function build(User $user): array
    {
        $items = [];

        if ($user->can('fees.manage')) {
            $upi = Payment::query()->where('status', PaymentStatus::AwaitingConfirmation->value)->count();
            if ($upi > 0) {
                $items[] = ['icon' => 'smartphone', 'text' => trans_choice(':count UPI payment to confirm|:count UPI payments to confirm', $upi, ['count' => digits($upi)]), 'url' => route('admin.payments.index')];
            }
            $owing = Family::query()->whereHas('dues', fn ($q) => $q->where('status', DueStatus::Due->value), '>=', 3)->count();
            if ($owing > 0) {
                $items[] = ['icon' => 'hand-coins', 'text' => trans_choice(':count family owes 3 months or more|:count families owe 3 months or more', $owing, ['count' => digits($owing)]), 'url' => route('admin.dues.index', ['min' => 3])];
            }
        }

        $enquiries = Enquiry::query()->open()->count();
        if ($enquiries > 0) {
            $items[] = ['icon' => 'user-check', 'text' => trans_choice(':count website enquiry to answer|:count website enquiries to answer', $enquiries, ['count' => digits($enquiries)]), 'url' => route('admin.enquiries.index')];
        }

        if ($user->can('chanda.cash')) {
            $cash = (int) $this->chandaBook->cashWithCollectors()->sum('amount');
            if ($cash > 0) {
                $items[] = ['icon' => 'wallet', 'text' => __(':amount cash is still with collectors', ['amount' => rupees($cash)]), 'url' => route('admin.chanda.cash')];
            }
        }

        if ($user->can('website.view')) {
            // An event in the next 10 days: check its details before the notice goes out (as on the dashboard)
            $soon = Event::query()->published()->upcoming()->where('starts_at', '<=', now()->addDays(10))->first();
            if ($soon !== null) {
                $days = (int) now()->startOfDay()->diffInDays($soon->starts_at->copy()->startOfDay());
                $items[] = ['icon' => 'calendar-days', 'text' => $days === 0
                    ? __(':event is today', ['event' => $soon->tr('title')])
                    : trans_choice(':event tomorrow|:event in :count days', $days, ['event' => $soon->tr('title'), 'count' => digits($days)]), 'url' => route('admin.events.edit', $soon)];
            }
            $drafts = Event::query()->where('status', PublishStatus::Draft->value)->count();
            if ($drafts > 0) {
                $items[] = ['icon' => 'file-text', 'text' => trans_choice(':count event is still a draft|:count events are still drafts', $drafts, ['count' => digits($drafts)]), 'url' => route('admin.events.index')];
            }
            $low = Plant::query()->published()->where('active', true)->where('stock', '<', 10)->count();
            if ($low > 0) {
                $items[] = ['icon' => 'sprout', 'text' => trans_choice(':count plant almost out of stock|:count plants almost out of stock', $low, ['count' => digits($low)]), 'url' => route('admin.nursery.stock')];
            }
        }

        // Notices: everyone on the committee may read them; members-only ones included
        $notices = Notice::query()->published()->reorder()->orderByDesc('published_at')->limit(4)
            ->get(['id', 'slug', 'title', 'title_bn', 'tag', 'tag_bn', 'audience', 'pinned', 'published_at']);

        return ['items' => $items, 'notices' => $notices];
    }
}
