<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SettingsRequest;
use App\Models\SiteSetting;
use App\Support\SiteMenu;
use App\Support\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Club details, the website menu and the search-engine text for each page.
 * site_settings has string keys, so each change is written to the audit log here.
 */
class SettingsController extends Controller
{
    public function edit(SiteSettings $site): View
    {
        Gate::authorize('viewAny', SiteSetting::class);

        return view('admin.settings', [
            'club' => $site->club(),
            'menu' => $site->menuSettings(),
            'menuItems' => SiteMenu::available(SiteMenu::MAIN),
            'seo' => $site->get('seo', []),
            'seoPages' => config('seo'),
        ]);
    }

    public function update(SettingsRequest $request, SiteSettings $site): RedirectResponse
    {
        foreach ($request->settings() as $key => $value) {
            $setting = SiteSetting::query()->firstOrNew(['key' => $key]);
            $old = $setting->value;

            if ($old === $value) {
                continue;
            }

            $setting->value = $value;
            $setting->save();

            activity('website')
                ->causedBy($request->user())
                ->event('updated')
                ->withProperties(['setting' => $key, 'old' => $old, 'attributes' => $value])
                ->log('Site setting "'.$key.'" changed');
        }

        $site->flush();

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved.');
    }
}
