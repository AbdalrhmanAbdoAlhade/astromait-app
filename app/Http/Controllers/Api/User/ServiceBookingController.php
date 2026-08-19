<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceBookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->serviceBookings()->with('service')->latest()->paginate(15)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date|after:now',
            'notes' => 'nullable|string',
        ]);

        $service = Service::where('status', 'active')->findOrFail($data['service_id']);

        $booking = $request->user()->serviceBookings()->create([
            'service_id' => $service->id,
            'status' => 'pending',
            'scheduled_at' => $data['scheduled_at'],
            'notes' => $data['notes'] ?? null,
            'price' => $service->price,
        ]);

        return response()->json($booking, 201);
    }
}
