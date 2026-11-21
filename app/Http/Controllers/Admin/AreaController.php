<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Console → Settings → Lanes and areas: the choices for "Where you live" on the join, volunteer and
 * chanda forms. Everyone on the committee may look; the Admin and the Website editor may change it.
 * "Somewhere else" is always offered last and is not edited here.
 */
class AreaController extends Controller
{
    public function edit(SiteSettings $site): View
    {
        Gate::authorize('website.view');

        return view('admin.areas', [
            'areas' => old('areas', $site->areaList()),
            'canEdit' => auth()->user()?->can('website.edit') ?? false,
        ]);
    }

    public function update(Request $request, SiteSettings $site): RedirectResponse
    {
        Gate::authorize('website.edit');

        $data = $request->validate([
            'areas' => ['required', 'array', 'min:1', 'max:40'],
            'areas.*.name' => ['required', 'string', 'max:60', 'distinct:ignore_case', 'not_in:'.SiteSettings::ELSEWHERE],
            'areas.*.name_bn' => ['nullable', 'string', 'max:60'],
        ]);

        $list = array_values(array_map(fn (array $a): array => ['name' => trim($a['name']), 'name_bn' => isset($a['name_bn']) && trim($a['name_bn']) !== '' ? trim($a['name_bn']) : null], $data['areas']));

        SiteSetting::query()->updateOrCreate(['key' => 'areas'], ['value' => $list]);
        $site->flush();

        activity('website')->causedBy($request->user())->withProperties(['areas' => array_column($list, 'name')])->event('updated')
            ->log('Lanes and areas updated');

        return redirect()->route('admin.areas.edit')->with('status', 'Lanes and areas saved. The join, volunteer and chanda forms use them now.');
    }
}
