<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Article::latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['title_en']);
        $data['author_id'] = $request->user()->id;

        return response()->json(Article::create($data), 201);
    }

    public function update(Request $request, Article $article): JsonResponse
    {
        $data = $this->validated($request, partial: true);
        $article->update($data);

        return response()->json($article->fresh());
    }

    public function destroy(Article $article): JsonResponse
    {
        $article->delete();

        return response()->json(['message' => 'تم حذف المقال.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'content_ar' => 'required|string',
            'content_en' => 'required|string',
            'cover_image' => 'nullable|string',
            'published_at' => 'nullable|date',
            'is_active' => 'nullable|boolean',
        ];

        if ($partial) {
            $rules = array_map(fn ($rule) => str_replace('required', 'sometimes', $rule), $rules);
        }

        return $request->validate($rules);
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $i = 1;

        while (Article::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
