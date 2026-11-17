<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Models\CateringPackage;
use App\Models\CommitteeMember;
use App\Models\Event;
use App\Models\Notice;
use App\Models\Page;
use App\Models\Plant;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Console → Website: every part of the public site the committee can edit, with drafts waiting.
 */
class WebsiteController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('website.view');

        $sections = [
            ['route' => 'admin.events.index', 'icon' => 'calendar-days', 'label' => __('Events'), 'model' => Event::class],
            ['route' => 'admin.notices.index', 'icon' => 'megaphone', 'label' => __('Notices'), 'model' => Notice::class],
            ['route' => 'admin.plants.index', 'icon' => 'sprout', 'label' => __('Nursery plants'), 'model' => Plant::class],
            ['route' => 'admin.packages.index', 'icon' => 'chef-hat', 'label' => __('Serving packages'), 'model' => CateringPackage::class],
            ['route' => 'admin.committee.index', 'icon' => 'users', 'label' => __('Committee'), 'model' => CommitteeMember::class],
        ];

        foreach ($sections as &$section) {
            $section['total'] = $section['model']::query()->count();
            $section['drafts'] = $section['model']::query()->where('status', PublishStatus::Draft->value)->count();
        }
        unset($section);

        return view('admin.website', [
            'sections' => $sections,
            'definitions' => config('pages'),
            'pages' => Page::query()->get()->keyBy('key'),
        ]);
    }
}
