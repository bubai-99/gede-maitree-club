<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PanjikaKind;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\PanjikaDay;
use App\Models\SiteSetting;
use App\Support\PanjikaData;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Console → Panjika: the puja days, parbon and wedding days (with lagna) the committee enters
 * from the club's panjika, and which panjika that is. The website's Panjika page shows them next
 * to the tithi worked out in the browser. Everyone on the committee may look; the Puja in-charge,
 * the Website editor and the Admin may change them (gate panjika.manage).
 */
class PanjikaController extends Controller
{
    public function index(Request $request, PanjikaData $data): View
    {
        Gate::authorize('website.view');
        $all = $request->query('show') === 'all';

        return view('admin.panjika.index', [
            'all' => $all,
            'days' => PanjikaDay::query()->when(! $all, fn ($q) => $q->fromToday())->with('event')->orderBy('date')->get(),
            'source' => $data->source(),
            'events' => Event::query()->where('starts_at', '>=', now()->subMonth())->orderBy('starts_at')->get(['id', 'title', 'title_bn', 'starts_at']),
            'canEdit' => $request->user()?->can('panjika.manage') ?? false,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('panjika.manage');

        $wedding = $request->input('kind') === PanjikaKind::Wedding->value;
        $data = $request->validate([
            'kind' => ['required', Rule::enum(PanjikaKind::class)],
            'date' => ['required', 'date', 'after_or_equal:today', 'before:+3 years'],
            'name' => [$wedding ? 'nullable' : 'required', 'string', 'max:120'],
            'name_bn' => ['nullable', 'string', 'max:120'],
            'tithi' => ['nullable', 'integer', 'between:0,29'],
            'lagna' => [$wedding ? 'required' : 'nullable', 'string', 'max:80'],
            'lagna_bn' => ['nullable', 'string', 'max:80'],
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id')],
        ]);

        $day = PanjikaDay::query()->create([
            'date' => $data['date'],
            'kind' => $data['kind'],
            'name' => $wedding ? 'Wedding day' : $data['name'],
            'name_bn' => $wedding ? 'বিয়ের দিন' : ($data['name_bn'] ?? null),
            'tithi' => $wedding ? null : ($data['tithi'] ?? null),
            'lagna' => $wedding ? $data['lagna'] : null,
            'lagna_bn' => $wedding ? ($data['lagna_bn'] ?? null) : null,
            'event_id' => $wedding ? null : ($data['event_id'] ?? null),
            'created_by' => $request->user()?->getKey(),
        ]);

        activity('panjika')->performedOn($day)->causedBy($request->user())->event('created')
            ->log("Panjika day added: {$day->name}, {$day->date->toDateString()}");

        return redirect()->route('admin.panjika.index')->with('status', 'Added. It shows on the website’s Panjika page.');
    }

    public function destroy(Request $request, PanjikaDay $day): RedirectResponse
    {
        Gate::authorize('panjika.manage');

        $day->delete();
        activity('panjika')->causedBy($request->user())->withProperties(['date' => $day->date->toDateString(), 'name' => $day->name])->event('deleted')
            ->log("Panjika day removed: {$day->name}, {$day->date->toDateString()}");

        return redirect()->route('admin.panjika.index')->with('status', 'Removed.');
    }

    public function source(Request $request, SiteSettings $site): RedirectResponse
    {
        Gate::authorize('panjika.manage');

        $data = $request->validate([
            'source' => ['required', 'string', 'max:120'],
            'source_bn' => ['nullable', 'string', 'max:120'],
        ]);

        SiteSetting::query()->updateOrCreate(['key' => 'panjika'], ['value' => ['source' => $data['source'], 'source_bn' => $data['source_bn'] ?? null]]);
        $site->flush();

        activity('panjika')->causedBy($request->user())->event('updated')->log("Panjika followed: {$data['source']}");

        return redirect()->route('admin.panjika.index')->with('status', 'Saved.');
    }
}
