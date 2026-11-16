<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryKind;
use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Everything sent from the website forms.
 */
class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Enquiry::class);

        $request->validate([
            'kind' => ['nullable', Rule::enum(EnquiryKind::class)],
            'show' => ['nullable', Rule::in(['open', 'handled', 'all'])],
        ]);
        $kind = $request->enum('kind', EnquiryKind::class);
        $show = $request->string('show', 'open')->toString();

        return view('admin.enquiries.index', [
            'kind' => $kind,
            'show' => $show,
            'enquiries' => Enquiry::query()
                ->when($kind, fn ($q) => $q->where('kind', $kind?->value))
                ->when($show === 'open', fn ($q) => $q->whereNull('handled_at'))
                ->when($show === 'handled', fn ($q) => $q->whereNotNull('handled_at'))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    public function show(Enquiry $enquiry): View
    {
        Gate::authorize('view', $enquiry);

        return view('admin.enquiries.show', ['enquiry' => $enquiry]);
    }

    /**
     * Mark as handled, or open it again.
     */
    public function update(Request $request, Enquiry $enquiry): RedirectResponse
    {
        Gate::authorize('update', $enquiry);

        $request->validate(['handled' => ['required', 'boolean']]);

        $enquiry->forceFill($request->boolean('handled')
            ? ['handled_at' => now(), 'handled_by' => $request->user()?->getKey()]
            : ['handled_at' => null, 'handled_by' => null])->save();

        activity('website')->performedOn($enquiry)->causedBy($request->user())
            ->event('updated')
            ->log($request->boolean('handled') ? 'Enquiry marked as handled' : 'Enquiry opened again');

        return redirect()->route('admin.enquiries.show', $enquiry)->with('status', 'Saved.');
    }
}
