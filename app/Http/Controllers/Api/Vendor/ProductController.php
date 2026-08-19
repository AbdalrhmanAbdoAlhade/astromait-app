<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        return response()->json(
            $vendor->products()->with(['images', 'category', 'certificate'])->latest()->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;
        $this->assertVendorApproved($vendor);

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name_en']);
        $data['vendor_id'] = $vendor->id;
        $data['status'] = 'pending'; // still needs admin approval to go live

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwnership($request, $product);

        $data = $this->validated($request, partial: true);
        $product->update($data);

        return response()->json($product->fresh());
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorizeOwnership($request, $product);
        $product->delete();

        return response()->json(['message' => 'تم حذف المنتج.']);
    }

    private function assertVendorApproved($vendor): void
    {
        if (! $vendor || ! $vendor->isApproved()) {
            abort(403, 'لازم يكون حسابك كتاجر معتمد.');
        }
    }

    private function authorizeOwnership(Request $request, Product $product): void
    {
        $vendor = $request->user()->vendorProfile;

        if (! $vendor || (int) $product->vendor_id !== (int) $vendor->id) {
            abort(403);
        }
    }

    private function validated(Request $request, bool $partial = false): array
    {
        $rules = [
            'category_id' => 'required|exists:categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|unique:products,sku',
            'weight_grams' => 'nullable|numeric|min:0',
        ];

        if ($partial) {
            $rules = array_map(fn ($rule) => str_replace('required', 'sometimes', $rule), $rules);
            $rules['sku'] = 'sometimes|string|unique:products,sku,'.$request->route('product')->id;
        }

        return $request->validate($rules);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
