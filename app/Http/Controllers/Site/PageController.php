<?php

namespace App\Http\Controllers\Site;

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

    public function chanda(SiteSettings $site): View
    {
        $chanda = $site->chanda();
        $event = isset($chanda['event_slug']) ? Event::query()->published()->where('slug', $chanda['event_slug'])->first() : null;
        $byArea = $event ? ChandaEntry::query()->where('event_id', $event->id)->selectRaw('area, sum(amount) as total')->groupBy('area')->pluck('total', 'area') : collect();

        return view('site.chanda', [
            'chanda' => $chanda,
            'event' => $event,
            'collected' => (int) $byArea->sum(),
            'byArea' => $byArea,
            'sponsors' => $event ? Sponsor::query()->where('event_id', $event->id)->orderByDesc('amount')->get() : collect(),
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
