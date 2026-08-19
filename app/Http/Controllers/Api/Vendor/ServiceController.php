<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        return response()->json(
            $vendor->services()->with(['category', 'certificate'])->latest()->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $vendor = $request->user()->vendorProfile;

        if (! $vendor || ! $vendor->isApproved()) {
            abort(403, 'لازم يكون حسابك كتاجر معتمد.');
        }

        $data = $this->validated($request);
        $data['slug'] = $this->uniqueSlug($data['name_en']);
        $data['vendor_id'] = $vendor->id;
        $data['status'] = 'pending';

        $service = Service::create($data);

        return response()->json($service, 201);
    }

    public function update(Request $request, Service $service): JsonResponse
    {
        $this->authorizeOwnership($request, $service);

        $data = $this->validated($request, partial: true);
        $service->update($data);

        return response()->json($service->fresh());
    }

    public function destroy(Request $request, Service $service): JsonResponse
    {
        $this->authorizeOwnership($request, $service);
        $service->delete();

        return response()->json(['message' => 'تم حذف الخدمة.']);
    }

    private function authorizeOwnership(Request $request, Service $service): void
    {
        $vendor = $request->user()->vendorProfile;

        if (! $vendor || (int) $service->vendor_id !== (int) $vendor->id) {
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
            'duration_minutes' => 'nullable|integer|min:1',
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

        while (Service::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
