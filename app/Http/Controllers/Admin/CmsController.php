<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\PublishesContent;
use App\Http\Controllers\Admin\Concerns\SavesWebsiteContent;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Website CMS lists and forms shared by events, notices, plants, packages and the committee.
 * Every action is authorised by the model's policy: any committee role may look,
 * only the Admin and the Website editor may change or publish.
 *
 * @template TModel of Model
 */
abstract class CmsController extends Controller
{
    use SavesWebsiteContent;

    /**
     * @return class-string<TModel>
     */
    abstract protected function modelClass(): string;

    /** Route name prefix, e.g. "admin.events". */
    abstract protected function routeName(): string;

    /** The route parameter, e.g. "event". */
    abstract protected function parameter(): string;

    /** Translated plural title, e.g. "Events". */
    abstract protected function title(): string;

    /** Translated singular, e.g. "Event". */
    abstract protected function singular(): string;

    /**
     * One list row: title, a line of detail and whether it is live.
     *
     * @return array{title: string, meta: string, published: bool}
     */
    abstract protected function row(Model $record): array;

    /**
     * @return Builder<TModel>
     */
    abstract protected function listQuery(): Builder;

    public function index(): View
    {
        Gate::authorize('viewAny', $this->modelClass());

        $records = $this->listQuery()->paginate(30);

        return view('admin.cms.index', [
            'title' => $this->title(),
            'singular' => $this->singular(),
            'routeName' => $this->routeName(),
            'modelClass' => $this->modelClass(),
            'records' => $records,
            'rows' => $records->getCollection()->map(fn (Model $record): array => $this->row($record) + ['record' => $record]),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', $this->modelClass());

        return $this->form(new ($this->modelClass()));
    }

    public function edit(Request $request): View
    {
        $record = $this->record($request);
        Gate::authorize('view', $record);

        return $this->form($record);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $record = $this->record($request);
        Gate::authorize('delete', $record);

        $record->delete();

        return redirect()->route($this->routeName().'.index')->with('status', __(':what deleted.', ['what' => $this->singular()]));
    }

    public function publish(Request $request): RedirectResponse
    {
        $record = $this->record($request);
        Gate::authorize('publish', $record);

        $record->publish();

        return back()->with('status', __(':what published.', ['what' => $this->singular()]));
    }

    public function unpublish(Request $request): RedirectResponse
    {
        $record = $this->record($request);
        Gate::authorize('publish', $record);

        $record->unpublish();

        return back()->with('status', __(':what taken off the website.', ['what' => $this->singular()]));
    }

    protected function form(Model $record): View
    {
        return view('admin.cms.'.$this->parameter(), [
            'record' => $record,
            'title' => $record->exists ? __('Edit') : __('Add'),
            'listTitle' => $this->title(),
            'routeName' => $this->routeName(),
            'parameter' => $this->parameter(),
        ]);
    }

    protected function record(Request $request): Model&PublishesContent
    {
        $record = $request->route($this->parameter());

        abort_unless($record instanceof Model && $record instanceof PublishesContent && is_a($record, $this->modelClass()), 404);

        return $record;
    }
}
