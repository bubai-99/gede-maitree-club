<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

/**
 * The website's header and footer links. Only links whose route exists are shown,
 * so a page that is not built yet never gives a broken link.
 */
final class SiteMenu
{
    /**
     * Default header menu (English labels are translation keys).
     *
     * @var list<array{route: string, label: string, class?: string}>
     */
    public const MAIN = [
        ['route' => 'events.index', 'label' => 'Events'],
        ['route' => 'notices.index', 'label' => 'Notices'],
        ['route' => 'catering', 'label' => 'Catering', 'class' => 'nav-catering'],
        ['route' => 'nursery', 'label' => 'Nursery', 'class' => 'nav-nursery'],
        ['route' => 'gallery', 'label' => 'Gallery'],
        ['route' => 'committee', 'label' => 'About'],
        ['route' => 'contact', 'label' => 'Contact'],
    ];

    /** @var list<array{route: string, label: string}> */
    public const FOOTER_CLUB = [
        ['route' => 'events.index', 'label' => 'Events'],
        ['route' => 'notices.index', 'label' => 'Notices'],
        ['route' => 'panjika', 'label' => 'Panjika'],
        ['route' => 'gallery', 'label' => 'Gallery'],
        ['route' => 'committee', 'label' => 'About & committee'],
        ['route' => 'chanda', 'label' => 'Chanda & accounts'],
    ];

    /** @var list<array{route: string, label: string}> */
    public const FOOTER_SERVICES = [
        ['route' => 'catering', 'label' => 'Maitree Serving'],
        ['route' => 'nursery', 'label' => 'Maitree Nursery'],
        ['route' => 'join', 'label' => 'Become a member'],
        ['route' => 'volunteer', 'label' => 'Volunteer'],
        ['route' => 'member.login', 'label' => 'Member login'],
        ['route' => 'admin.login', 'label' => 'Committee login'],
    ];

    /**
     * @param  list<array{route: string, label: string, class?: string}>  $items
     * @return list<array{route: string, label: string, class?: string}>
     */
    public static function available(array $items): array
    {
        return array_values(array_filter($items, fn (array $item): bool => Route::has($item['route'])));
    }
}
