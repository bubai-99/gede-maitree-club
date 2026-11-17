<?php

namespace App\Http\Controllers\Admin;

use App\Chanda\ChandaBook;
use App\Chanda\ChandaException;
use App\Enums\ChandaMode;
use App\Enums\SponsorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChandaAreaRequest;
use App\Http\Requests\Admin\ChandaBudgetRequest;
use App\Http\Requests\Admin\SponsorReceiveRequest;
use App\Http\Requests\Admin\SponsorRequest;
use App\Models\ChandaArea;
use App\Models\Event;
use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Setting up a chanda drive (Puja in-charge, Treasurer, Admin): budget heads and target, whether
 * the website shows the totals, areas with their collectors, and sponsors.
 */
class ChandaSetupController extends Controller
{
    public function editBudget(Event $event): View
    {
        Gate::authorize('manageChanda', $event);

        return view('admin.chanda.budget', ['event' => $event]);
    }

    public function updateBudget(ChandaBudgetRequest $request, Event $event): RedirectResponse
    {
        // The event model logs every change (old and new budget) in the audit log.
        $event->update($request->budgetData());

        return redirect()->route('admin.chanda.show', $event)->with('status', 'Budget saved.');
    }

    public function storeArea(ChandaAreaRequest $request, Event $event): RedirectResponse
    {
        $event->chandaAreas()->create([...$request->areaData(), 'sort' => (int) $event->chandaAreas()->max('sort') + 1]);

        return back()->with('status', 'Area added.');
    }

    public function updateArea(ChandaAreaRequest $request, ChandaArea $area): RedirectResponse
    {
        $area->update($request->areaData());

        return back()->with('status', 'Area saved.');
    }

    public function destroyArea(ChandaArea $area): RedirectResponse
    {
        Gate::authorize('manageChanda', $area->event);

        if ($area->entries()->exists()) {
            return back()->with('error', 'This area has receipts, so it stays. Change its collector instead.');
        }

        $area->delete();

        return back()->with('status', 'Area removed.');
    }

    public function storeSponsor(SponsorRequest $request, Event $event, ChandaBook $book): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        $sponsor = $event->sponsors()->create([...$request->sponsorData(), 'status' => SponsorStatus::Promised]);

        if ($request->boolean('received')) {
            $book->receiveSponsor($sponsor, $sponsor->amount, ChandaMode::from($request->string('mode', 'cash')->toString()), $request->input('upi_ref'), $user);
        }

        return back()->with('status', 'Sponsor added.');
    }

    public function receiveSponsor(SponsorReceiveRequest $request, Sponsor $sponsor, ChandaBook $book): RedirectResponse
    {
        $user = $request->user();
        assert($user instanceof User);

        try {
            $entry = $book->receiveSponsor($sponsor, $request->integer('amount'), ChandaMode::from($request->string('mode')->toString()), $request->input('upi_ref'), $user);
        } catch (ChandaException $e) {
            return back()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return redirect()->route('admin.chanda.receipt', $entry)
            ->with('status', 'Sponsorship entered in the Club book. Receipt :receipt.')
            ->with('status_replace', ['receipt' => $entry->receipt_no]);
    }

    public function destroySponsor(Sponsor $sponsor): RedirectResponse
    {
        Gate::authorize('manageChanda', $sponsor->event);

        if ($sponsor->isReceived() || $sponsor->receipts()->exists()) {
            return back()->with('error', 'This sponsor has a receipt, so it stays.');
        }

        $sponsor->delete();

        return back()->with('status', 'Sponsor removed.');
    }
}
