<?php

namespace App\Http\Controllers\Site;

use App\Enums\NoticeAudience;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(): View
    {
        return view('site.notices.index', [
            'notices' => Notice::query()->forPublic()->paginate(12),
        ]);
    }

    public function show(Notice $notice): View
    {
        abort_unless($notice->isPublished() && $notice->audience === NoticeAudience::Public, 404);

        return view('site.notices.show', [
            'notice' => $notice,
            'more' => Notice::query()->forPublic()->whereKeyNot($notice->getKey())->limit(3)->get(),
        ]);
    }
}
