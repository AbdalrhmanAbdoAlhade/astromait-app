<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::where('is_active', true)->whereNull('parent_id')->with('children');

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        return response()->json($query->orderBy('order')->get());
    }
}
