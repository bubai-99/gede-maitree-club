<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Sidebar and bottom-bar links for the member app and the committee console.
 * Links to screens that are not built yet are left out, and console links
 * are only shown to people whose role can open them.
 */
final class Navigation
{
    /**
     * Member app: groups of [route, icon, label].
     *
     * @var list<array{group: ?string, items: list<array{route: string, icon: string, label: string}>}>
     */
    public const MEMBER = [
        ['group' => null, 'items' => [
            ['route' => 'member.home', 'icon' => 'house', 'label' => 'Home'],
            ['route' => 'member.pay', 'icon' => 'indian-rupee', 'label' => 'Pay dues'],
            ['route' => 'member.dues', 'icon' => 'scroll-text', 'label' => 'Statement'],
            ['route' => 'member.receipts.index', 'icon' => 'receipt-text', 'label' => 'Receipts'],
            ['route' => 'member.card', 'icon' => 'id-card', 'label' => 'Member cards'],
        ]],
        ['group' => 'Club', 'items' => [
            ['route' => 'member.events.index', 'icon' => 'calendar-days', 'label' => 'Events & passes'],
            ['route' => 'member.notices.index', 'icon' => 'megaphone', 'label' => 'Notices'],
        ]],
        ['group' => 'Account', 'items' => [
            ['route' => 'member.profile', 'icon' => 'users', 'label' => 'Family & profile'],
        ]],
    ];

    /** @var list<array{route: string, icon: string, label: string}> */
    public const MEMBER_TABS = [
        ['route' => 'member.home', 'icon' => 'house', 'label' => 'Home'],
        ['route' => 'member.pay', 'icon' => 'indian-rupee', 'label' => 'Pay'],
        ['route' => 'member.card', 'icon' => 'id-card', 'label' => 'Card'],
        ['route' => 'member.events.index', 'icon' => 'calendar-days', 'label' => 'Events'],
        ['route' => 'member.profile', 'icon' => 'user', 'label' => 'Profile'],
    ];

    /**
     * Committee console: groups of [route, icon, label, ability].
     * The ability is checked with Gate (see AuthServiceProvider / policies).
     *
     * @var list<array{group: ?string, items: list<array{route: string, icon: string, label: string, ability: string}>}>
     */
    public const ADMIN = [
        ['group' => null, 'items' => [
            ['route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'label' => 'Today', 'ability' => 'console.view'],
        ]],
        ['group' => 'Members', 'items' => [
            ['route' => 'admin.collect', 'icon' => 'hand-coins', 'label' => 'Collect payment', 'ability' => 'fees.manage'],
            ['route' => 'admin.dues.index', 'icon' => 'bell-ring', 'label' => 'Dues & reminders', 'ability' => 'fees.manage'],
            ['route' => 'admin.payments.index', 'icon' => 'smartphone', 'label' => 'Online payments', 'ability' => 'fees.manage'],
        ]],
        ['group' => 'Books', 'items' => [
            ['route' => 'admin.khata.index', 'icon' => 'book-open', 'label' => 'Khata', 'ability' => 'khata.open'],
            ['route' => 'admin.receipts.index', 'icon' => 'receipt', 'label' => 'Receipts register', 'ability' => 'fees.manage'],
            ['route' => 'admin.cash', 'icon' => 'landmark', 'label' => 'Cash & bank', 'ability' => 'khata.open'],
            ['route' => 'admin.reports.index', 'icon' => 'chart-column', 'label' => 'Reports', 'ability' => 'reports.open'],
            ['route' => 'admin.chanda.index', 'icon' => 'hand-heart', 'label' => 'Puja chanda', 'ability' => 'chanda.open'],
            ['route' => 'admin.chanda.cash', 'icon' => 'wallet', 'label' => 'Chanda cash', 'ability' => 'chanda.cash'],
        ]],
        ['group' => 'Club', 'items' => [
            ['route' => 'admin.events.index', 'icon' => 'calendar-days', 'label' => 'Events', 'ability' => 'website.view'],
            ['route' => 'admin.scan', 'icon' => 'scan-line', 'label' => 'Gate scanner', 'ability' => 'passes.scan'],
        ]],
        ['group' => 'Website', 'items' => [
            ['route' => 'admin.website', 'icon' => 'globe', 'label' => 'Website', 'ability' => 'website.view'],
            ['route' => 'admin.notices.index', 'icon' => 'megaphone', 'label' => 'Notices', 'ability' => 'website.view'],
            ['route' => 'admin.plants.index', 'icon' => 'sprout', 'label' => 'Nursery plants', 'ability' => 'website.view'],
            ['route' => 'admin.packages.index', 'icon' => 'chef-hat', 'label' => 'Serving packages', 'ability' => 'website.view'],
            ['route' => 'admin.pages.index', 'icon' => 'file-text', 'label' => 'Pages', 'ability' => 'website.view'],
            ['route' => 'admin.committee.index', 'icon' => 'users', 'label' => 'Committee', 'ability' => 'website.view'],
            ['route' => 'admin.enquiries.index', 'icon' => 'inbox', 'label' => 'Enquiries', 'ability' => 'console.view'],
        ]],
        ['group' => 'Admin', 'items' => [
            ['route' => 'admin.settings.edit', 'icon' => 'settings', 'label' => 'Club settings', 'ability' => 'website.view'],
            ['route' => 'admin.activity', 'icon' => 'history', 'label' => 'Audit log', 'ability' => 'activity.view'],
        ]],
    ];

    /** @var list<array{route: string, icon: string, label: string, ability: string}> */
    public const ADMIN_TABS = [
        ['route' => 'admin.dashboard', 'icon' => 'layout-dashboard', 'label' => 'Today', 'ability' => 'console.view'],
        ['route' => 'admin.collect', 'icon' => 'hand-coins', 'label' => 'Collect', 'ability' => 'fees.manage'],
        ['route' => 'admin.website', 'icon' => 'globe', 'label' => 'Website', 'ability' => 'website.view'],
        ['route' => 'admin.events.index', 'icon' => 'calendar-days', 'label' => 'Events', 'ability' => 'website.view'],
        ['route' => 'admin.enquiries.index', 'icon' => 'inbox', 'label' => 'Enquiries', 'ability' => 'console.view'],
    ];

    /**
     * @param  list<array{group: ?string, items: list<array<string, string>>}>  $groups
     * @return list<array{group: ?string, items: list<array<string, mixed>>}>
     */
    public static function groups(array $groups, ?User $user = null): array
    {
        $out = [];

        foreach ($groups as $group) {
            $items = self::items($group['items'], $user);

            if ($items !== []) {
                $out[] = ['group' => $group['group'], 'items' => $items];
            }
        }

        return $out;
    }

    /**
     * @param  list<array<string, string>>  $items
     * @return list<array<string, mixed>>
     */
    public static function items(array $items, ?User $user = null): array
    {
        $visible = array_filter($items, function (array $item) use ($user): bool {
            if (! Route::has($item['route'])) {
                return false;
            }

            return ! isset($item['ability']) || ($user !== null && $user->can($item['ability']));
        });

        return array_values(array_map(fn (array $item): array => $item + [
            'url' => route($item['route']),
            'active' => request()->routeIs($item['route'], $item['route'].'.*', str_replace('.index', '.*', $item['route'])),
        ], $visible));
    }
}
