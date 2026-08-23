<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $articles = Article::query()
            ->published()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('title', 'like', '%'.$search.'%')
                        ->orWhere('excerpt', 'like', '%'.$search.'%')
                        ->orWhere('content_html', 'like', '%'.$search.'%');
                });
            })
            ->ordered()
            ->paginate(9)
            ->withQueryString();

        return view('blog.index', [
            'articles' => $articles,
            'search' => $search,
            'blogSeenAt' => now()->toIso8601String(),
        ]);
    }

    public function show(string $slug): View
    {
        $article = Article::query()
            ->published()
            ->where('slug', $slug)
            ->firstOrFail();

        $article->recordPublicView();

        $seenAt = now()->toIso8601String();

        return view('blog.show', compact('article', 'seenAt'));
    }

    public function newCount(Request $request): JsonResponse
    {
        $query = Article::query()->published();

        $since = trim((string) $request->query('since', ''));
        if ($since !== '') {
            try {
                $query->where('published_at', '>', \Illuminate\Support\Carbon::parse($since));
            } catch (\Throwable) {
                // Nieprawidłowa data — zwróć liczbę wszystkich opublikowanych artykułów.
            }
        }

        return response()->json([
            'count' => $query->count(),
        ]);
    }
}
