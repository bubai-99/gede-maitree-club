<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DuesRequest;
use App\Models\Due;
use App\Models\Family;
use App\Models\User;
use App\Payments\DueService;
use App\Support\DueReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Console → Dues & reminders: who owes how much, filtered by status, months overdue and
 * area; WhatsApp reminders; adding a month's dues; waiving months with a reason.
 */
class DueController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Due::class);

        $request->validate([
            'status' => ['nullable', Rule::enum(DueStatus::class)],
            'min' => ['nullable', 'integer', Rule::in([1, 3, 6])],
            'area' => ['nullable', 'string', 'max:100'],
            'month' => ['nullable', 'date_format:Y-m'],
        ]);

        $status = $request->enum('status', DueStatus::class) ?? DueStatus::Due;
        $min = $request->integer('min', 1) ?: 1;
        $area = $request->string('area')->toString();
        $month = $request->filled('month') ? Carbon::createFromFormat('!Y-m', $request->string('month')->toString()) : null;

        $owing = fn ($q) => $q->where('status', DueStatus::Due->value);

        $families = null;
        $dues = null;

        if ($status === DueStatus::Due) {
            $families = Family::query()
                ->whereHas('dues', $owing, '>=', $min)
                ->when($area !== '', fn ($q) => $q->where('area', $area))
                ->withCount(['dues as due_months' => $owing])
                ->withSum(['dues as due_total' => $owing], 'amount')
                ->orderByDesc('due_months')
                ->orderBy('family_no')
                ->paginate(50)
                ->withQueryString();
        } else {
            $dues = Due::query()
                ->with(['family', 'payments', 'waiver'])
                ->where('status', $status->value)
                ->when($month, fn ($q) => $q->where('period', $month?->toDateString()))
                ->when($area !== '', fn ($q) => $q->whereHas('family', fn ($f) => $f->where('area', $area)))
                ->orderByDesc('period')
                ->orderBy('family_id')
                ->paginate(50)
                ->withQueryString();
        }

        $thisMonth = now()->startOfMonth();

        return view('admin.dues.index', [
            'status' => $status,
            'min' => $min,
            'area' => $area,
            'month' => $month,
            'families' => $families,
            'dues' => $dues,
            'areas' => Family::query()->whereNotNull('area')->distinct()->orderBy('area')->pluck('area'),
            'totalOwed' => (int) Due::query()->where('status', DueStatus::Due->value)->sum('amount'),
            'owingCount' => Family::query()->whereHas('dues', $owing)->count(),
            'counts' => collect([1, 3, 6])->mapWithKeys(fn (int $m): array => [$m => Family::query()->whereHas('dues', $owing, '>=', $m)->count()]),
            'thisMonth' => $thisMonth,
            'thisMonthGenerated' => Due::query()->where('period', $thisMonth->toDateString())->count(),
        ]);
    }

    public function generate(DuesRequest $request, DueService $dues): RedirectResponse
    {
        $month = Carbon::createFromFormat('!Y-m', $request->string('month')->toString()) ?? now();
        $added = $dues->generate($month);

        activity('payments')->causedBy($request->user())
            ->withProperties(['month' => $month->format('Y-m'), 'added' => $added])
            ->event('dues_generated')
            ->log("Dues added for {$month->format('F Y')}: {$added} families");

        return back()->with('status', ':count families got a due for :month.')
            ->with('status_replace', ['count' => digits($added), 'month' => local_date($month, 'F Y')]);
    }

    /**
     * Remind the chosen families on WhatsApp. One family: straight to WhatsApp. Several:
     * a page of links (a browser opens one new tab per click, not many at once).
     */
    public function remind(DuesRequest $request): View|RedirectResponse
    {
        $families = Family::query()
            ->whereIn('id', $request->ids('families'))
            ->withCount(['dues as due_months' => fn ($q) => $q->where('status', DueStatus::Due->value)])
            ->withSum(['dues as due_total' => fn ($q) => $q->where('status', DueStatus::Due->value)], 'amount')
            ->orderBy('family_no')
            ->get();

        $reminders = $families->map(function (Family $family): array {
            $months = (int) $family->getAttribute('due_months');
            $total = (int) $family->getAttribute('due_total');
            $message = DueReminder::message($family, $months, $total);

            return ['family' => $family, 'months' => $months, 'total' => $total, 'message' => $message, 'link' => DueReminder::link($family, $message)];
        });

        Family::query()->whereIn('id', $families->modelKeys())->update(['last_reminded_at' => now()]);

        activity('payments')->causedBy($request->user())
            ->withProperties(['families' => $families->pluck('family_no')->all()])
            ->event('reminded')
            ->log('WhatsApp reminders opened for '.$families->count().' families');

        $only = $reminders->count() === 1 ? $reminders->first() : null;

        if ($only !== null && $only['link'] !== null) {
            return redirect()->away($only['link']);
        }

        return view('admin.dues.remind', ['reminders' => $reminders]);
    }

    public function family(Family $family): View
    {
        Gate::authorize('viewAny', Due::class);

        $dues = $family->dues()->with(['payments', 'waiver'])->reorder()->orderByDesc('period')->get();

        return view('admin.dues.family', [
            'family' => $family,
            'dues' => $dues,
            'payable' => $family->dues()->payable()->pluck('id')->all(),
            'owed' => (int) $dues->where('status', DueStatus::Due)->sum('amount'),
        ]);
    }

    public function waive(DuesRequest $request, Family $family, DueService $dues): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $count = $dues->waive($family, $request->ids('dues'), $request->string('reason')->toString(), $user);

        return back()->with('status', ':count months waived.')->with('status_replace', ['count' => digits($count)]);
    }
}
