<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->where('status', 'active')
            ->with(['primaryImage', 'category', 'vendor', 'certificate']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->integer('vendor_id'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->float('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->float('max_price'));
        }

        $products = $query->latest()->paginate($request->integer('per_page', 20));

        return response()->json($products);
    }

    public function show(Product $product): JsonResponse
    {
        if ($product->status !== 'active') {
            abort(404);
        }

        $product->increment('views_count');

        $product->load(['images', 'variants', 'category', 'vendor', 'certificate', 'activeAuction']);

        return response()->json($product);
    }
}
