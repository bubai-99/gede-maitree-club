<?php

namespace App\Http\Controllers\Site;

use App\Chanda\ChandaBook;
use App\Enums\PlantCategory;
use App\Http\Controllers\Controller;
use App\Models\CateringPackage;
use App\Models\ChandaEntry;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Family;
use App\Models\Plant;
use App\Models\Sponsor;
use App\Support\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The website's other pages. Every list shows published content only.
 */
class PageController extends Controller
{
    public function catering(): View
    {
        return view('site.catering', [
            'packages' => CateringPackage::query()->published()->orderBy('sort')->get(),
        ]);
    }

    public function nursery(Request $request): View
    {
        $request->validate(['category' => ['nullable', Rule::enum(PlantCategory::class)]]);
        $category = $request->enum('category', PlantCategory::class);

        return view('site.nursery', [
            'category' => $category,
            'plants' => Plant::query()->published()->where('active', true)
                ->when($category, fn ($q) => $q->where('category', $category?->value))
                ->orderBy('sort')->orderBy('name')->get(),
        ]);
    }

    public function gallery(Request $request, SiteSettings $site): View
    {
        $albums = $site->galleryAlbums();
        $request->validate(['album' => ['nullable', Rule::in(array_column($albums, 'key'))]]);
        $album = $request->string('album')->toString();

        return view('site.gallery', [
            'albums' => $albums,
            'album' => $album,
            'days' => array_values(array_filter($site->galleryDays(), fn (array $day): bool => $album === '' || $day['album'] === $album)),
        ]);
    }

    /**
     * The chanda drive. Totals, areas and sponsors are shown only when the committee has chosen
     * to publish them (events.chanda_public); donors are named only when they agreed.
     */
    public function chanda(SiteSettings $site, ChandaBook $book): View
    {
        $chanda = $site->chanda();
        $slug = $chanda['event_slug'] ?? null;
        $event = (is_string($slug) ? Event::query()->published()->collectingChanda()->where('slug', $slug)->first() : null)
            ?? Event::query()->published()->collectingChanda()->where('chanda_public', true)->first();
        $public = $event !== null && $event->chanda_public;

        return view('site.chanda', [
            'chanda' => $chanda,
            'event' => $event,
            'public' => $public,
            'summary' => $public ? $book->summary($event) : null,
            'areas' => $public ? $event->chandaAreas()->with('collector:id,name')->get() : collect(),
            'donors' => $public ? ChandaEntry::query()->valid()->where('event_id', $event->id)->where('show_name', true)->whereNull('sponsor_id')
                ->latest('received_at')->limit(30)->pluck('donor_name') : collect(),
            'sponsors' => $public ? Sponsor::query()->where('event_id', $event->id)->orderByDesc('amount')->get() : collect(),
        ]);
    }

    public function committee(): View
    {
        return view('site.committee', [
            'committee' => CommitteeMember::query()->published()->orderBy('sort')->get(),
            'families' => Family::query()->count(),
        ]);
    }

    public function contact(): View
    {
        return view('site.contact', [
            'committee' => CommitteeMember::query()->published()->orderBy('sort')->get()->keyBy('role'),
        ]);
    }

    public function join(): View
    {
        return view('site.join');
    }

    public function volunteer(): View
    {
        return view('site.volunteer', [
            'events' => Event::query()->published()->upcoming()->limit(3)->get(),
        ]);
    }

    public function panjika(): View
    {
        return view('site.panjika');
    }

    public function privacy(): View
    {
        return view('site.privacy');
    }

    public function terms(): View
    {
        return view('site.terms');
    }
}
