<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Notice;
use Illuminate\View\View;

/**
 * Events and notices inside the member app (members-only notices included).
 */
class ClubController extends Controller
{
    public function events(): View
    {
        return view('member.events.index', [
            'upcoming' => Event::query()->published()->upcoming()->get(),
            'past' => Event::query()->published()->past()->limit(5)->get(),
        ]);
    }

    public function event(Event $event): View
    {
        abort_unless($event->isPublished(), 404);

        return view('member.events.show', ['event' => $event]);
    }

    public function notices(): View
    {
        return view('member.notices.index', [
            'notices' => Notice::query()->forMembers()->paginate(15),
        ]);
    }

    public function notice(Notice $notice): View
    {
        abort_unless($notice->isPublished(), 404);

        return view('member.notices.show', ['notice' => $notice]);
    }
}
