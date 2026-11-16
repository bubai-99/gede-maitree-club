<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DueStatus;
use App\Enums\FamilyStatus;
use App\Enums\PaymentStatus;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Models\Due;
use App\Models\Enquiry;
use App\Models\Event;
use App\Models\Family;
use App\Models\Payment;
use App\Models\Plant;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Console → Today: the numbers the committee checks every evening.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.dashboard', [
            'user' => $request->user(),
            'activeFamilies' => Family::query()->where('status', FamilyStatus::Active->value)->count(),
            'familiesWithDues' => Due::query()->where('status', DueStatus::Due->value)->distinct()->count('family_id'),
            'outstanding' => (int) Due::query()->where('status', DueStatus::Due->value)->sum('amount'),
            'collectedThisMonth' => (int) Payment::query()->where('status', PaymentStatus::Confirmed->value)
                ->where('paid_at', '>=', now()->startOfMonth())->sum('amount'),
            'openEnquiries' => Enquiry::query()->open()->count(),
            'latestEnquiries' => Enquiry::query()->open()->latest()->limit(5)->get(),
            'upcoming' => Event::query()->published()->upcoming()->limit(4)->get(),
            'drafts' => Event::query()->where('status', PublishStatus::Draft->value)->count(),
            'lowStock' => Plant::query()->published()->where('active', true)->where('stock', '<', 10)->orderBy('stock')->limit(5)->get(),
        ]);
    }
}
