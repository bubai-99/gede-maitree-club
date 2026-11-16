<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\CommitteeMemberRequest;
use App\Models\CommitteeMember;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

/**
 * @extends CmsController<CommitteeMember>
 */
class CommitteeMemberController extends CmsController
{
    protected function modelClass(): string
    {
        return CommitteeMember::class;
    }

    protected function routeName(): string
    {
        return 'admin.committee';
    }

    protected function parameter(): string
    {
        return 'committee';
    }

    protected function title(): string
    {
        return __('Committee');
    }

    protected function singular(): string
    {
        return __('Committee member');
    }

    protected function listQuery(): Builder
    {
        return CommitteeMember::query()->orderBy('sort');
    }

    protected function row(Model $record): array
    {
        if (! $record instanceof CommitteeMember) {
            throw new \InvalidArgumentException('Expected a committee member.');
        }

        return [
            'title' => (string) $record->name,
            'meta' => (string) $record->role,
            'published' => $record->isPublished(),
        ];
    }

    public function store(CommitteeMemberRequest $request): RedirectResponse
    {
        $member = CommitteeMember::query()->create([...$request->memberData(), ...$this->statusFromButton($request)]);
        $this->savePhoto($request, $member);

        return $this->saved($request, 'admin.committee.index', __('Committee member'));
    }

    public function update(CommitteeMemberRequest $request, CommitteeMember $committee): RedirectResponse
    {
        $committee->update([...$request->memberData(), ...$this->statusFromButton($request, $committee)]);
        $this->savePhoto($request, $committee);

        return $this->saved($request, 'admin.committee.index', __('Committee member'));
    }
}
