<?php

namespace App\Support;

use App\Enums\ReviewKind;
use App\Models\Review;
use Illuminate\Support\Collection;

/**
 * The published reviews of the serving team or the nursery, with their average and spread,
 * for the "What families / gardeners say" sections on the website.
 */
final class Reviews
{
    /**
     * @return array{items: Collection<int, Review>, count: int, average: float, spread: array<int, int>}
     */
    public static function for(ReviewKind $kind): array
    {
        $items = Review::query()->published()->where('kind', $kind->value)
            ->orderBy('sort')->orderByDesc('reviewed_on')->orderByDesc('id')->get();

        $spread = [];
        foreach ([5, 4, 3, 2, 1] as $stars) {
            $spread[$stars] = $items->where('rating', $stars)->count();
        }

        return [
            'items' => $items,
            'count' => $items->count(),
            'average' => $items->isEmpty() ? 0.0 : round((float) $items->avg('rating'), 1),
            'spread' => $spread,
        ];
    }
}
