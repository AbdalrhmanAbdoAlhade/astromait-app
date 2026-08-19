<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['vendor.user:id,name', 'category']);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function approve(Product $product): JsonResponse
    {
        $product->update(['status' => 'active', 'rejection_reason' => null]);

        return response()->json($product->fresh());
    }

    public function reject(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate(['reason' => 'required|string']);

        $product->update(['status' => 'rejected', 'rejection_reason' => $data['reason']]);

        return response()->json($product->fresh());
    }
}
