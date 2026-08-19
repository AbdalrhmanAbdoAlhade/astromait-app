<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Banner::orderBy('order')->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        return response()->json(Banner::create($data), 201);
    }

    public function update(Request $request, Banner $banner): JsonResponse
    {
        $data = $this->validated($request, partial: true);
        $banner->update($data);

        return response()->json($banner->fresh());
    }

    public function destroy(Banner $banner): JsonResponse
    {
        $banner->delete();

        return response()->json(['message' => 'تم حذف البانر.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'image' => 'required|string',
            'link_type' => 'required|in:product,service,auction,category,external,none',
            'link_id' => 'nullable|integer',
            'external_url' => 'nullable|string|url',
            'position' => 'required|string',
            'is_active' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'order' => 'nullable|integer|min:0',
        ];

        if ($partial) {
            $rules = array_map(fn ($rule) => str_replace('required', 'sometimes', $rule), $rules);
        }

        return $request->validate($rules);
    }
}
