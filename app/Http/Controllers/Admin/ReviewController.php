<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ReviewRequest;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * Console → Website → Reviews: customers' words about the serving team and the nursery, shown on
 * the catering and nursery pages once published. Add them only with the customer's permission.
 *
 * @extends CmsController<Review>
 */
class ReviewController extends CmsController
{
    protected function modelClass(): string
    {
        return Review::class;
    }

    protected function routeName(): string
    {
        return 'admin.reviews';
    }

    protected function parameter(): string
    {
        return 'review';
    }

    protected function title(): string
    {
        return __('Reviews');
    }

    protected function singular(): string
    {
        return __('Review');
    }

    protected function listQuery(): Builder
    {
        return Review::query()->orderBy('kind')->orderBy('sort')->orderByDesc('reviewed_on')->orderByDesc('id');
    }

    protected function row(Model $record): array
    {
        if (! $record instanceof Review) {
            throw new \InvalidArgumentException('Expected a review.');
        }

        return [
            'title' => mb_strimwidth($record->body, 0, 90, '…'),
            'meta' => collect([$record->kind->label(), str_repeat('★', $record->rating), $record->who, $record->reviewed_on ? local_date($record->reviewed_on, 'M Y') : null])->filter()->join(' · '),
            'published' => $record->isPublished(),
        ];
    }

    public function store(ReviewRequest $request): RedirectResponse
    {
        Review::query()->create([...$request->reviewData(), ...$this->statusFromButton($request)]);

        return $this->saved($request, 'admin.reviews.index', __('Review'));
    }

    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $review->update([...$request->reviewData(), ...$this->statusFromButton($request, $review)]);

        return $this->saved($request, 'admin.reviews.index', __('Review'));
    }
}
