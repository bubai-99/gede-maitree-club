<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\NoticeRequest;
use App\Models\Notice;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * @extends CmsController<Notice>
 */
class NoticeController extends CmsController
{
    protected function modelClass(): string
    {
        return Notice::class;
    }

    protected function routeName(): string
    {
        return 'admin.notices';
    }

    protected function parameter(): string
    {
        return 'notice';
    }

    protected function title(): string
    {
        return __('Notices');
    }

    protected function singular(): string
    {
        return __('Notice');
    }

    protected function listQuery(): Builder
    {
        return Notice::query()->orderByDesc('pinned')->orderByDesc('published_at')->orderByDesc('id');
    }

    protected function row(Model $record): array
    {
        if (! $record instanceof Notice) {
            throw new \InvalidArgumentException('Expected a notice.');
        }

        return [
            'title' => (string) $record->title,
            'meta' => $record->audience->label().($record->pinned ? ' · '.__('Pinned') : ''),
            'published' => $record->isPublished(),
        ];
    }

    public function store(NoticeRequest $request): RedirectResponse
    {
        Notice::query()->create([...$request->noticeData(), ...$this->statusFromButton($request)]);

        return $this->saved($request, 'admin.notices.index', __('Notice'));
    }

    public function update(NoticeRequest $request, Notice $notice): RedirectResponse
    {
        $notice->update([...$request->noticeData(), ...$this->statusFromButton($request, $notice)]);

        return $this->saved($request, 'admin.notices.index', __('Notice'));
    }
}
