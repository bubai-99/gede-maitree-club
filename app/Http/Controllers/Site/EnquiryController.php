<?php

namespace App\Http\Controllers\Site;

use App\Enums\EnquiryKind;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\CateringEnquiryRequest;
use App\Http\Requests\Site\ChandaPledgeRequest;
use App\Http\Requests\Site\ContactRequest;
use App\Http\Requests\Site\JoinRequest;
use App\Http\Requests\Site\PhotosRequest;
use App\Http\Requests\Site\PublicFormRequest;
use App\Http\Requests\Site\VolunteerRequest;
use App\Models\Enquiry;
use Illuminate\Http\RedirectResponse;

/**
 * The website forms. Everything sent is saved as an enquiry for the committee
 * (Console → Enquiries). The routes are rate limited ("forms" limiter).
 */
class EnquiryController extends Controller
{
    public function contact(ContactRequest $request): RedirectResponse
    {
        return $this->save($request, EnquiryKind::Contact, 'Thank you. We usually reply within a day, by phone or WhatsApp.');
    }

    public function join(JoinRequest $request): RedirectResponse
    {
        return $this->save($request, EnquiryKind::Join, 'Your application has reached the committee. It is approved at the next meeting, and the secretary will call you.');
    }

    public function catering(CateringEnquiryRequest $request): RedirectResponse
    {
        return $this->save($request, EnquiryKind::Catering, 'Thank you. The catering in-charge will call within a day to confirm the guests, timing and price.');
    }

    public function volunteer(VolunteerRequest $request): RedirectResponse
    {
        return $this->save($request, EnquiryKind::Volunteer, 'Thank you for signing up. The secretary will call or WhatsApp you within two days.');
    }

    public function photos(PhotosRequest $request): RedirectResponse
    {
        return $this->save($request, EnquiryKind::Photos, 'Thank you. The committee will get in touch to collect the photos.');
    }

    public function chanda(ChandaPledgeRequest $request): RedirectResponse
    {
        return $this->save($request, EnquiryKind::Chanda, 'Thank you for your pledge. The treasurer or your lane’s volunteer will be in touch.');
    }

    private function save(PublicFormRequest $request, EnquiryKind $kind, string $thanks): RedirectResponse
    {
        Enquiry::query()->create([
            'kind' => $kind,
            'name' => $request->string('name')->trim()->toString(),
            'phone' => $request->phone(),
            'email' => $request->input('email'),
            'payload' => $request->payload(),
            'locale' => app()->getLocale(),
        ]);

        return back()->with('status', $thanks);
    }
}
