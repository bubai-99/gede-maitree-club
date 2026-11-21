<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Console → News categories: add, rename, order and remove (posts in a removed category stay,
 * without a category).
 */
class NewsCategoryController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', NewsCategory::class);

        return view('admin.news-categories', [
            'categories' => NewsCategory::query()->withCount('posts')->orderBy('sort')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', NewsCategory::class);
        $data = $this->validated($request);
        NewsCategory::query()->create($data + ['slug' => $this->slug($data['name'])]);

        return redirect()->route('admin.news-categories.index')->with('status', 'Category added.');
    }

    public function update(Request $request, NewsCategory $category): RedirectResponse
    {
        Gate::authorize('update', $category);
        $category->update($this->validated($request, $category));

        return redirect()->route('admin.news-categories.index')->with('status', 'Saved.');
    }

    public function destroy(NewsCategory $category): RedirectResponse
    {
        Gate::authorize('delete', $category);
        $category->delete();

        return redirect()->route('admin.news-categories.index')->with('status', 'Category removed. Its posts stay, without a category.');
    }

    /**
     * @return array{name: string, name_bn: ?string, sort: int}
     */
    private function validated(Request $request, ?NewsCategory $category = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80', Rule::unique('news_categories', 'name')->ignore($category?->id)],
            'name_bn' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        return ['name' => $data['name'], 'name_bn' => $data['name_bn'] ?? null, 'sort' => (int) ($data['sort'] ?? 0)];
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        for ($i = 2; NewsCategory::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }
}
