<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Category::with('parent')->orderBy('order')->paginate(30));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name_en']);

        return response()->json(Category::create($data), 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $this->validated($request, partial: true);
        $category->update($data);

        return response()->json($category->fresh());
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'تم حذف القسم.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'parent_id' => 'nullable|exists:categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'image' => 'nullable|string',
            'type' => 'required|in:product,service',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ];

        if ($partial) {
            $rules = array_map(fn ($rule) => str_replace('required', 'sometimes', $rule), $rules);
        }

        return $request->validate($rules);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
