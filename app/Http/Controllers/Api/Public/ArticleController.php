<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Article::published()->latest('published_at')->paginate($request->integer('per_page', 20))
        );
    }

    public function show(Article $article): JsonResponse
    {
        if (! $article->is_active || ! $article->published_at || $article->published_at->isFuture()) {
            abort(404);
        }

        return response()->json($article);
    }
}
