<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Enums\PublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Spatie\MediaLibrary\HasMedia;

/**
 * Shared steps for the website CMS controllers: draft or publish from the
 * button pressed, and the uploaded photo.
 */
trait SavesWebsiteContent
{
    /**
     * "Save as draft" or "Save and publish".
     *
     * @return array{status: PublishStatus, published_at?: Carbon}
     */
    protected function statusFromButton(Request $request, ?Model $current = null): array
    {
        if ($request->input('action') === 'publish') {
            return ['status' => PublishStatus::Published, 'published_at' => $current?->getAttribute('published_at') ?? now()];
        }

        return ['status' => PublishStatus::Draft];
    }

    /**
     * Store the uploaded photo (if any) in the record's "photo" collection on the public disk.
     */
    protected function savePhoto(Request $request, HasMedia $model): void
    {
        if ($request->boolean('remove_photo')) {
            $model->clearMediaCollection('photo');
        }

        if ($request->hasFile('photo_upload')) {
            $model->addMediaFromRequest('photo_upload')->toMediaCollection('photo');
        }
    }

    protected function saved(Request $request, string $route, string $what): RedirectResponse
    {
        $message = $request->input('action') === 'publish'
            ? __(':what published.', ['what' => $what])
            : __(':what saved as a draft.', ['what' => $what]);

        return redirect()->route($route)->with('status', $message);
    }
}
