<?php

namespace App\Http\Controllers\Site;

use App\Enums\FamilyStatus;
use App\Http\Controllers\Controller;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Family;
use App\Models\Notice;
use App\Support\SiteSettings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(SiteSettings $site): View
    {
        $upcoming = Event::query()->published()->upcoming()->limit(4)->get();

        return view('site.home', [
            'nextEvent' => $upcoming->first(),
            'upcoming' => $upcoming,
            'notices' => Notice::query()->forPublic()->limit(3)->get(),
            'committee' => CommitteeMember::query()->published()->orderBy('sort')->limit(4)->get(),
            'families' => Family::query()->where('status', FamilyStatus::Active->value)->count(),
            'gallery' => $site->galleryDays(),
            'lastPuja' => $site->chanda()['last_puja'] ?? null,
        ]);
    }
}
