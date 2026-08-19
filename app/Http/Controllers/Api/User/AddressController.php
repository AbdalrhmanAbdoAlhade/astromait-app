<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->addresses);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if ($data['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address = $request->user()->addresses()->create($data);

        return response()->json($address, 201);
    }

    public function update(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwnership($request, $address);

        $data = $this->validated($request);

        if ($data['is_default'] ?? false) {
            $request->user()->addresses()->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json($address->fresh());
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorizeOwnership($request, $address);
        $address->delete();

        return response()->json(['message' => 'تم حذف العنوان.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => 'required|in:shipping,billing',
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:30',
            'country' => 'nullable|string|max:2',
            'city' => 'required|string|max:255',
            'area' => 'nullable|string|max:255',
            'details' => 'required|string',
            'postal_code' => 'nullable|string|max:20',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'is_default' => 'nullable|boolean',
        ]);
    }

    private function authorizeOwnership(Request $request, Address $address): void
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
