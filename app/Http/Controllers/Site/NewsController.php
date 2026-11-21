<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\NewsCategory;
use App\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Website → News: stories by category, newest first (featured on top), and each story with its
 * cover photo, text, videos and photos.
 */
class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['category' => ['nullable', 'string', 'max:120']]);

        $categories = NewsCategory::query()
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('sort')->orderBy('name')->get()
            ->filter(fn (NewsCategory $c): bool => $c->posts_count > 0)->values();

        $category = $request->filled('category') ? $categories->firstWhere('slug', $request->string('category')->toString()) : null;
        abort_if($request->filled('category') && $category === null, 404);

        $posts = NewsPost::query()->published()->with('category')
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->orderByDesc('featured')->orderByDesc('published_at')->orderByDesc('id')
            ->paginate(10)->withQueryString();

        return view('site.news.index', [
            'categories' => $categories,
            'category' => $category,
            'total' => NewsPost::query()->published()->count(),
            'posts' => $posts,
        ]);
    }

    public function show(NewsPost $post): View
    {
        abort_unless($post->isPublished(), 404);
        $post->load(['category', 'media']);

        return view('site.news.show', [
            'post' => $post,
            'more' => NewsPost::query()->published()->with('category')->whereKeyNot($post->getKey())
                ->orderByRaw('category_id = ? desc', [$post->category_id ?? 0])
                ->orderByDesc('published_at')->limit(3)->get(),
        ]);
    }
}
