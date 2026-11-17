<?php

namespace App\Http\Controllers\Admin;

use App\Chanda\ChandaBook;
use App\Chanda\ChandaException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CashHandoverRequest;
use App\Models\CashHandover;
use App\Models\ChandaEntry;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Chanda cash with collectors, and the treasurer receiving it (counted, with a note when the
 * count differs). The cash was joma in the Club book when each receipt was written.
 */
class CashHandoverController extends Controller
{
    public function index(ChandaBook $book): View
    {
        Gate::authorize('receiveCash', ChandaEntry::class);

        return view('admin.chanda.cash', [
            'holders' => $book->cashWithCollectors(),
            'handovers' => CashHandover::query()->with(['collector', 'receiver', 'event'])->latest()->limit(30)->get(),
        ]);
    }

    public function store(CashHandoverRequest $request, ChandaBook $book): RedirectResponse
    {
        $user = $request->user();
        $collector = User::query()->findOrFail($request->integer('collector_id'));
        assert($user instanceof User);

        try {
            $handover = $book->handOver($collector, $request->integer('counted'), $request->input('note'), $user);
        } catch (ChandaException $e) {
            return back()->withInput()->with('error', $e->getMessage())->with('status_replace', $e->replace);
        }

        return back()->with('status', ':amount received from :name.')
            ->with('status_replace', ['amount' => rupees($handover->counted), 'name' => $collector->name]);
    }
}
