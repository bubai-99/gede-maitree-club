<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Http\Controllers\Admin\Concerns\SavesWebsiteContent;
use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Editable page text (config/pages.php): banner photo, headings and paragraphs in both
 * languages. Saving keeps a draft; publishing makes the draft live.
 */
class PageController extends Controller
{
    use SavesWebsiteContent;

    public function index(): View
    {
        Gate::authorize('viewAny', Page::class);

        $pages = Page::query()->get()->keyBy('key');

        return view('admin.cms.pages.index', [
            'definitions' => config('pages'),
            'pages' => $pages,
        ]);
    }

    public function edit(string $key): View
    {
        $definition = $this->definition($key);
        $page = Page::query()->firstOrNew(['key' => $key], ['title' => $definition['label']]);
        Gate::authorize('view', $page);

        $values = $page->draft ?? $page->blocks ?? [];
        $fields = [];

        foreach ($definition['fields'] as $name => $field) {
            $fields[$name] = $field + [
                'value' => $values[$name] ?? __($field['default'], [], 'en'),
                'value_bn' => $values[$name.'_bn'] ?? __($field['default'], [], 'bn'),
            ];
        }

        return view('admin.cms.pages.edit', [
            'key' => $key,
            'page' => $page,
            'definition' => $definition,
            'fields' => $fields,
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $definition = $this->definition($key);
        $page = Page::query()->firstOrNew(['key' => $key], ['title' => $definition['label']]);
        Gate::authorize($page->exists ? 'update' : 'create', $page->exists ? $page : Page::class);

        $rules = [
            'action' => ['required', Rule::in(['draft', 'publish'])],
            'photo_upload' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];

        foreach (array_keys($definition['fields']) as $name) {
            $rules["fields.{$name}"] = ['nullable', 'string', 'max:3000'];
            $rules["fields.{$name}_bn"] = ['nullable', 'string', 'max:3000'];
        }

        $validated = $request->validate($rules);
        $draft = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $validated['fields'] ?? []);

        $page->fill(['title' => $definition['label'], 'draft' => $draft]);

        if ($request->input('action') === 'publish') {
            $page->fill(['blocks' => $draft, 'status' => PublishStatus::Published, 'published_at' => now()]);
        } elseif (! $page->exists) {
            $page->fill(['status' => PublishStatus::Draft]);
        }

        $page->save();
        $this->savePhoto($request, $page);

        return redirect()->route('admin.pages.edit', $key)->with('status', $request->input('action') === 'publish'
            ? __(':what published.', ['what' => __($definition['label'])])
            : __(':what saved as a draft.', ['what' => __($definition['label'])]));
    }

    /**
     * @return array{label: string, photo: string, fields: array<string, array{label: string, default: string, long?: bool}>}
     */
    private function definition(string $key): array
    {
        $definition = config("pages.{$key}");
        abort_unless(is_array($definition), 404);

        return $definition;
    }
}
