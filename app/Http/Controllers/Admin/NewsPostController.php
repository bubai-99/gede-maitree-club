<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NewsPostRequest;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Console → Website → News: stories with a cover photo, text, videos and photos.
 *
 * @extends CmsController<NewsPost>
 */
class NewsPostController extends CmsController
{
    protected function modelClass(): string
    {
        return NewsPost::class;
    }

    protected function routeName(): string
    {
        return 'admin.news';
    }

    protected function parameter(): string
    {
        return 'post';
    }

    protected function title(): string
    {
        return __('News');
    }

    protected function singular(): string
    {
        return __('News post');
    }

    protected function listQuery(): Builder
    {
        return NewsPost::query()->with('category')->orderByDesc('featured')->orderByRaw('published_at desc nulls first')->orderByDesc('id');
    }

    protected function row(Model $record): array
    {
        if (! $record instanceof NewsPost) {
            throw new \InvalidArgumentException('Expected a news post.');
        }

        return [
            'title' => (string) $record->title,
            'meta' => collect([
                $record->category?->tr('name'),
                $record->published_at ? local_date($record->published_at) : null,
                $record->featured ? __('Featured') : null,
                $record->videos ? trans_choice(':count video|:count videos', count($record->videos), ['count' => digits(count($record->videos))]) : null,
            ])->filter()->join(' · '),
            'published' => $record->isPublished(),
        ];
    }

    protected function form(Model $record): View
    {
        return parent::form($record)->with('categories', NewsCategory::query()->orderBy('sort')->orderBy('name')->get());
    }

    public function store(NewsPostRequest $request): RedirectResponse
    {
        $post = NewsPost::query()->create([...$request->postData(), 'author_id' => $request->user()?->getKey(), ...$this->statusFromButton($request)]);
        $this->saveMedia($request, $post);

        return $this->saved($request, 'admin.news.index', __('News post'));
    }

    public function update(NewsPostRequest $request, NewsPost $post): RedirectResponse
    {
        $post->update([...$request->postData(), ...$this->statusFromButton($request, $post)]);
        $this->saveMedia($request, $post);

        return $this->saved($request, 'admin.news.index', __('News post'));
    }

    private function saveMedia(NewsPostRequest $request, NewsPost $post): void
    {
        $this->savePhoto($request, $post);

        $remove = array_map('intval', (array) $request->input('remove_images', []));
        $post->getMedia('images')->whereIn('id', $remove)->each->delete();

        foreach ((array) $request->file('images_upload', []) as $file) {
            $post->addMedia($file)->toMediaCollection('images');
        }
    }
}
