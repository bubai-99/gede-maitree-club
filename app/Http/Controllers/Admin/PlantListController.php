<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plant;
use App\Models\SiteSetting;
use App\Support\PlantLists;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * Console → Settings → Plant lists: the nursery's plant types (the chips on the nursery page),
 * sun and water choices. Everyone on the committee may look; the Admin and the Website editor
 * may change them. A choice that plants still use can't be removed: move those plants first.
 */
class PlantListController extends Controller
{
    public function edit(PlantLists $lists): View
    {
        Gate::authorize('website.view');

        $current = [];
        foreach (PlantLists::KINDS as $kind) {
            $current[$kind] = old($kind, $lists->items($kind));
        }

        return view('admin.plant-lists', [
            'lists' => $current,
            'canEdit' => auth()->user()?->can('website.edit') ?? false,
        ]);
    }

    public function update(Request $request, PlantLists $lists, SiteSettings $site): RedirectResponse
    {
        Gate::authorize('website.edit');

        $rules = [];
        foreach (PlantLists::KINDS as $kind) {
            $rules[$kind] = [$kind === 'category' ? 'required' : 'present', 'array', 'max:30'];
            $rules[$kind.'.*.key'] = ['nullable', 'string', 'max:40', 'alpha_dash:ascii'];
            $rules[$kind.'.*.name'] = ['required', 'string', 'max:40', 'distinct:ignore_case'];
            $rules[$kind.'.*.name_bn'] = ['nullable', 'string', 'max:40'];
        }

        $validator = validator($request->all(), $rules);
        $validator->after(function (Validator $v) use ($request, $lists): void {
            foreach (PlantLists::KINDS as $kind) {
                $kept = array_filter(array_column((array) $request->input($kind, []), 'key'));
                foreach (array_diff($lists->keys($kind), $kept) as $gone) {
                    $count = Plant::query()->where($kind, $gone)->count();
                    if ($count > 0) {
                        $v->errors()->add($kind, trans_choice(':name is used by :count plant. Choose another for it first, then remove this.|:name is used by :count plants. Choose another for them first, then remove this.', $count, [
                            'name' => $lists->label($kind, $gone) ?? $gone,
                            'count' => digits($count),
                        ]));
                    }
                }
            }
        });
        $data = $validator->validate();

        $saved = [];
        foreach (PlantLists::KINDS as $kind) {
            $taken = [];
            $saved[$kind] = [];
            foreach ((array) ($data[$kind] ?? []) as $row) {
                $name = trim((string) $row['name']);
                // A new choice gets its key from its English name; an old one keeps its key, so plants keep it
                $key = (string) ($row['key'] ?? '') ?: (substr(Str::slug($name), 0, 34) ?: $kind);
                $base = $key;
                for ($i = 2; in_array($key, $taken, true); $i++) {
                    $key = $base.'-'.$i;
                }
                $taken[] = $key;
                $bn = trim((string) ($row['name_bn'] ?? ''));
                $saved[$kind][] = ['key' => $key, 'name' => $name, 'name_bn' => $bn !== '' ? $bn : null];
            }
        }

        SiteSetting::query()->updateOrCreate(['key' => 'plant_lists'], ['value' => $saved]);
        $site->flush();

        activity('website')->causedBy($request->user())
            ->withProperties(array_map(fn (array $list): array => array_column($list, 'name'), $saved))
            ->event('updated')->log('Plant lists updated');

        return redirect()->route('admin.plant-lists.edit')->with('status', 'Plant lists saved. The nursery page and the plant form use them now.');
    }
}
