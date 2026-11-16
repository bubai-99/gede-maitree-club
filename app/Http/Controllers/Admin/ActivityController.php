<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

/**
 * The audit log: who changed what on the website, and when.
 */
class ActivityController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('activity.view');

        return view('admin.activity', [
            'activities' => Activity::query()->with('causer')->latest()->paginate(40),
        ]);
    }
}
