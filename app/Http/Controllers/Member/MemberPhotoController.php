<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * ID card photos: a family uploads, changes or removes the photo of anyone in the family. The
 * photo is served only to that family and to the committee, never as a public file.
 */
class MemberPhotoController extends Controller
{
    public function show(Request $request, Member $member): BinaryFileResponse
    {
        $this->authorizeView($request, $member);
        $media = $member->getFirstMedia('photo');
        abort_if($media === null, 404);

        $path = $media->hasGeneratedConversion('card') ? $media->getPath('card') : $media->getPath();
        abort_unless(is_file($path), 404);

        return response()->file($path, [
            'Content-Type' => $media->hasGeneratedConversion('card') ? 'image/jpeg' : $media->mime_type,
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function store(Request $request, Member $member): RedirectResponse
    {
        $this->authorizeFamily($request, $member);
        $request->validate([
            'photo' => ['required', 'image', Rule::file()->types(['jpg', 'jpeg', 'png', 'webp'])->max(8 * 1024)],
        ]);

        $member->addMediaFromRequest('photo')->usingFileName('member-'.$member->id.'.'.$request->file('photo')?->guessExtension())->toMediaCollection('photo');

        activity('members')->performedOn($member)->causedBy($request->user())->event('photo')->log("ID card photo changed for {$member->member_no}");

        return back()->with('status', 'Photo saved. It is on the ID card now.');
    }

    public function destroy(Request $request, Member $member): RedirectResponse
    {
        $this->authorizeFamily($request, $member);
        $member->clearMediaCollection('photo');

        activity('members')->performedOn($member)->causedBy($request->user())->event('photo')->log("ID card photo removed for {$member->member_no}");

        return back()->with('status', 'Photo removed.');
    }

    private function authorizeFamily(Request $request, Member $member): void
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->family_id !== null && $user->family_id === $member->family_id, 403);
    }

    private function authorizeView(Request $request, Member $member): void
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($user->family_id !== null && $user->family_id === $member->family_id) {
            return;
        }

        abort_unless($user->can('members.manage'), 403);
    }
}
