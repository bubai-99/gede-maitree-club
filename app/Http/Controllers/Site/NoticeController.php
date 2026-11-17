<?php

namespace App\Http\Controllers\Site;

use App\Enums\NoticeAudience;
use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NoticeController extends Controller
{
    public function index(Request $request): View
    {
        // Filter chips: each tag in use, with how many notices carry it
        $tags = Notice::query()->forPublic()->whereNotNull('tag')->get(['tag', 'tag_bn'])
            ->groupBy('tag')->map(fn ($group): array => ['tag' => (string) $group->first()?->tag, 'label' => (string) $group->first()?->tr('tag'), 'count' => $group->count()])
            ->values();

        $request->validate(['tag' => ['nullable', 'string', 'max:60']]);
        $tag = $request->string('tag')->toString();

        return view('site.notices.index', [
            'tags' => $tags,
            'tag' => $tag,
            'total' => Notice::query()->forPublic()->count(),
            'notices' => Notice::query()->forPublic()->when($tag !== '', fn ($q) => $q->where('tag', $tag))->paginate(12)->withQueryString(),
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
